<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Approval service for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Approval service implementation.
 */
final class approval_service {
    /**
     * Set state.
     *
     * @param \stdClass $version version
     * @param string $state state
     * @param int $actorid actorid
     * @param string $comment comment
     * @return void
     */
    private static function set_state(\stdClass $version, string $state, int $actorid, string $comment): void {
        global $DB;
        workflow_state_machine::assert($version->status, $state);
        audit_history::transition($version, $version->status, $state, $actorid, $comment);
        $DB->update_record('local_syllabusworkflow_version', (object)[
            'id' => $version->id, 'status' => $state, 'timemodified' => time(),
        ]);
    }
    /**
     * Assign.
     *
     * @param \stdClass $version version
     * @param int $stage stage
     * @param int $fallback fallback
     * @param int $actorid actorid
     * @return \stdClass
     */
    private static function assign(\stdClass $version, int $stage, int $fallback, int $actorid): \stdClass {
        global $DB;
        $template = syllabus_repository::template($version->templateversionid);
        $stages = schema::decode($template->workflowjson);
        if (!isset($stages[$stage])) {
            throw new \moodle_exception('invalidstages', 'local_syllabusworkflow');
        }
        $reviewerid = (int)$stages[$stage]['reviewerid'] ?: $fallback;
        $plan = syllabus_repository::plan($version->planid);
        if (!$reviewerid || !has_capability('local/syllabusworkflow:approve',
                syllabus_repository::course_context($plan), $reviewerid)) {
            throw new \moodle_exception('invalidreviewer', 'local_syllabusworkflow');
        }
        $record = (object)[
            'versionid' => $version->id, 'stage' => $stage,
            'reviewerid' => $reviewerid,
            'duedate' => $stages[$stage]['days'] ? time() + (int)$stages[$stage]['days'] * DAYSECS : 0,
            'status' => 'pending', 'assignedby' => $actorid, 'timecreated' => time(),
        ];
        $record->id = $DB->insert_record('local_syllabusworkflow_assignment', $record);
        return $record;
    }
    /**
     * Submit.
     *
     * @param int $planid planid
     * @param int $reviewerid reviewerid
     * @param int $actorid actorid
     * @param ?int $expectedversionid expectedversionid
     * @return void
     */
    public static function submit(int $planid, int $reviewerid, int $actorid, ?int $expectedversionid = null): void {
        global $DB;
        $assigned = version_manager::locked($planid, function() use ($DB, $planid, $reviewerid, $actorid, $expectedversionid) {
            $transaction = $DB->start_delegated_transaction();
            $plan = syllabus_repository::plan($planid);
            $version = syllabus_repository::version($plan->currentversionid);
            if ($expectedversionid !== null && $version->id != $expectedversionid) {
                throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
            }
            transition_validator::require($plan, $version, $actorid, 'submit');
            workflow_state_machine::assert($version->status, workflow_state_machine::SUBMITTED);
            $template = syllabus_repository::template($version->templateversionid);
            schema::validate_payload(schema::decode($version->payload), schema::decode($template->schemajson));
            $assignment = self::assign($version, 0, $reviewerid, $actorid);
            self::set_state($version, workflow_state_machine::SUBMITTED, $actorid, '');
            $transaction->allow_commit();
            return [$version->id, $assignment->reviewerid];
        });
        notification_service::send($assigned[0], $assigned[1], 'submitted', 'initial');
    }
    /**
     * Begin.
     *
     * @param int $planid planid
     * @param int $actorid actorid
     * @param ?int $expectedversionid expectedversionid
     * @return void
     */
    public static function begin(int $planid, int $actorid, ?int $expectedversionid = null): void {
        global $DB;
        version_manager::locked($planid, function() use ($DB, $planid, $actorid, $expectedversionid) {
            $transaction = $DB->start_delegated_transaction();
            $plan = syllabus_repository::plan($planid);
            $version = syllabus_repository::version($plan->currentversionid);
            if ($expectedversionid !== null && $version->id != $expectedversionid) {
                throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
            }
            transition_validator::require($plan, $version, $actorid, 'review');
            self::require_pending($version->id, $actorid);
            self::set_state($version, workflow_state_machine::UNDER_REVIEW, $actorid, '');
            $transaction->allow_commit();
        });
    }
    /**
     * Require pending.
     *
     * @param int $versionid versionid
     * @param int $userid userid
     * @return \stdClass
     */
    private static function require_pending(int $versionid, int $userid): \stdClass {
        global $DB;
        return $DB->get_record('local_syllabusworkflow_assignment', [
            'versionid' => $versionid, 'reviewerid' => $userid, 'status' => 'pending',
        ], '*', MUST_EXIST);
    }
    /**
     * Decide.
     *
     * @param int $planid planid
     * @param int $actorid actorid
     * @param bool $approve approve
     * @param string $comment comment
     * @param ?int $expectedversionid expectedversionid
     * @return void
     */
    public static function decide(int $planid, int $actorid, bool $approve, string $comment, ?int $expectedversionid = null): void {
        global $DB;
        $notify = version_manager::locked($planid, function() use ($DB, $planid, $actorid, $approve, $comment, $expectedversionid) {
            $transaction = $DB->start_delegated_transaction();
            $plan = syllabus_repository::plan($planid);
            $version = syllabus_repository::version($plan->currentversionid);
            if ($expectedversionid !== null && $version->id != $expectedversionid) {
                throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
            }
            $operation = $approve ? 'approve' : 'changes';
            transition_validator::require($plan, $version, $actorid, $operation);
            if ($version->status !== workflow_state_machine::UNDER_REVIEW ||
                    (!$approve && trim($comment) === '')) {
                throw new \moodle_exception('invaliddecision', 'local_syllabusworkflow');
            }
            $assignment = self::require_pending($version->id, $actorid);
            $DB->insert_record('local_syllabusworkflow_approval', (object)[
                'versionid' => $version->id, 'stage' => $assignment->stage,
                'reviewerid' => $actorid, 'decision' => $approve ? 'approved' : 'changes_requested',
                'comment' => $comment, 'timecreated' => time(),
            ]);
            $DB->set_field('local_syllabusworkflow_assignment', 'status', 'completed', ['id' => $assignment->id]);
            if (!$approve) {
                self::set_state($version, workflow_state_machine::CHANGES_REQUESTED, $actorid, $comment);
                $recipient = $version->createdby;
                $kind = 'changesrequested';
            } else {
                $template = syllabus_repository::template($version->templateversionid);
                $stages = schema::decode($template->workflowjson);
                if (isset($stages[$assignment->stage + 1])) {
                    $next = self::assign($version, $assignment->stage + 1, $actorid, $actorid);
                    $recipient = $next->reviewerid;
                    $kind = 'submitted';
                } else {
                    self::set_state($version, workflow_state_machine::APPROVED, $actorid, $comment);
                    $recipient = $version->createdby;
                    $kind = 'approved';
                }
            }
            $transaction->allow_commit();
            return [$version->id, $recipient, $kind, (string)$assignment->stage];
        });
        notification_service::send($notify[0], $notify[1], $notify[2], $notify[3]);
    }
    /**
     * Delegate.
     *
     * @param int $planid planid
     * @param int $newreviewer newreviewer
     * @param int $actorid actorid
     * @param ?int $expectedversionid expectedversionid
     * @return void
     */
    public static function delegate(int $planid, int $newreviewer, int $actorid, ?int $expectedversionid = null): void {
        global $DB;
        version_manager::locked($planid, function() use ($DB, $planid, $newreviewer, $actorid, $expectedversionid) {
            $transaction = $DB->start_delegated_transaction();
            $plan = syllabus_repository::plan($planid);
            $context = syllabus_repository::course_context($plan);
            require_capability('local/syllabusworkflow:manage', \context_system::instance(), $actorid);
            require_capability('local/syllabusworkflow:approve', $context, $newreviewer);
            $version = syllabus_repository::version($plan->currentversionid);
            if ($expectedversionid !== null && $version->id != $expectedversionid) {
                throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
            }
            if (!in_array($version->status, [workflow_state_machine::SUBMITTED,
                    workflow_state_machine::UNDER_REVIEW], true)) {
                throw new \moodle_exception('invalidtransition', 'local_syllabusworkflow');
            }
            $pending = $DB->get_record('local_syllabusworkflow_assignment', [
                'versionid' => $version->id, 'status' => 'pending',
            ], '*', MUST_EXIST);
            $DB->set_field('local_syllabusworkflow_assignment', 'reviewerid', $newreviewer, ['id' => $pending->id]);
            audit_history::log($version, 'delegated', $actorid, (string)$newreviewer);
            $transaction->allow_commit();
        });
    }
}
