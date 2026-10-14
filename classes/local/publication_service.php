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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Publication service for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Publication service implementation.
 */
final class publication_service {
    /**
     * Publish.
     *
     * @param int $planid planid
     * @param int $actorid actorid
     * @param ?int $expectedversionid expectedversionid
     * @return void
     */
    public static function publish(int $planid, int $actorid, ?int $expectedversionid = null): void {
        global $DB;
        $notify = version_manager::locked($planid, function() use ($DB, $planid, $actorid, $expectedversionid) {
            $transaction = $DB->start_delegated_transaction();
            $plan = syllabus_repository::plan($planid);
            $version = syllabus_repository::version($plan->currentversionid);
            if ($expectedversionid !== null && $version->id != $expectedversionid) {
                throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
            }
            transition_validator::require($plan, $version, $actorid, 'publish');
            workflow_state_machine::assert($version->status, workflow_state_machine::PUBLISHED);
            if ($plan->publishedversionid) {
                $prior = syllabus_repository::version($plan->publishedversionid);
                if ($prior->status === workflow_state_machine::PUBLISHED) {
                    audit_history::transition($prior, $prior->status,
                        workflow_state_machine::ARCHIVED, $actorid, 'Superseded');
                    $DB->set_field('local_syllabusworkflow_version', 'status', workflow_state_machine::ARCHIVED,
                        ['id' => $prior->id]);
                }
            }
            audit_history::transition($version, $version->status, workflow_state_machine::PUBLISHED, $actorid, '');
            $DB->set_field('local_syllabusworkflow_version', 'status', workflow_state_machine::PUBLISHED, ['id' => $version->id]);
            $DB->update_record('local_syllabusworkflow_plan', (object)[
                'id' => $planid, 'publishedversionid' => $version->id, 'timemodified' => time(),
            ]);
            $DB->insert_record('local_syllabusworkflow_publication', (object)[
                'planid' => $plan->id, 'versionid' => $version->id,
                'courseid' => $plan->courseid, 'actorid' => $actorid, 'timecreated' => time(),
            ]);
            $transaction->allow_commit();
            return [$version->id, $version->createdby];
        });
        notification_service::send($notify[0], $notify[1], 'published', (string)$notify[0]);
    }
    /**
     * Archive.
     *
     * @param int $planid planid
     * @param int $actorid actorid
     * @param ?int $expectedversionid expectedversionid
     * @return void
     */
    public static function archive(int $planid, int $actorid, ?int $expectedversionid = null): void {
        global $DB;
        version_manager::locked($planid, function() use ($DB, $planid, $actorid, $expectedversionid) {
            $transaction = $DB->start_delegated_transaction();
            require_capability('local/syllabusworkflow:manage', \context_system::instance(), $actorid);
            $plan = syllabus_repository::plan($planid);
            $version = syllabus_repository::version($plan->currentversionid);
            if ($expectedversionid !== null && $version->id != $expectedversionid) {
                throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
            }
            workflow_state_machine::assert($version->status, workflow_state_machine::ARCHIVED);
            audit_history::transition($version, $version->status,
                workflow_state_machine::ARCHIVED, $actorid, 'Administrative archive');
            $DB->update_record('local_syllabusworkflow_version', (object)[
                'id' => $version->id, 'status' => workflow_state_machine::ARCHIVED,
                'timemodified' => time(),
            ]);
            if ($plan->publishedversionid == $version->id) {
                $DB->set_field('local_syllabusworkflow_plan', 'publishedversionid', null, ['id' => $planid]);
            }
            $DB->set_field('local_syllabusworkflow_plan', 'timemodified', time(), ['id' => $planid]);
            $transaction->allow_commit();
        });
    }

}
