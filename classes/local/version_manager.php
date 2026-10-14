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
 * Version manager for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
defined('MOODLE_INTERNAL') || die();
final class version_manager {
    public static function create_plan(int $courseid, int $templateversionid, int $actorid): \stdClass {
        global $DB;
        $context = \context_course::instance($courseid);
        require_capability('local/syllabusworkflow:edit', $context, $actorid);
        $template = syllabus_repository::template($templateversionid);
        $fields = schema::decode($template->schemajson);
        $payload = [];
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $author = $DB->get_record('user', ['id' => $actorid], '*', MUST_EXIST);
        $presets = [
            'courseidentification' => $course->fullname . ' (' . $course->shortname . ')',
            'disciplinename' => $course->fullname,
            'disciplinecode' => $course->idnumber,
            'teacher' => fullname($author),
        ];
        foreach ($fields as $field) {
            $payload[$field['key']] = $field['default'] ?? ($presets[$field['key']] ?? '');
        }
        $now = time();
        $transaction = $DB->start_delegated_transaction();
        $planid = $DB->insert_record('local_swf_plan', (object)[
            'courseid' => $courseid, 'templateversionid' => $templateversionid,
            'createdby' => $actorid, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $version = self::insert($planid, 1, null, $template, $payload, $actorid);
        $DB->set_field('local_swf_plan', 'currentversionid', $version->id, ['id' => $planid]);
        audit_history::log($version, 'created', $actorid);
        $transaction->allow_commit();
        return $version;
    }
    public static function snapshot(\stdClass $plan, \stdClass $template): string {
        global $DB;
        $course = $DB->get_record('course', ['id' => $plan->courseid], 'id,fullname,shortname,idnumber', MUST_EXIST);
        $author = $DB->get_record('user', ['id' => $plan->createdby], 'id,firstname,lastname', MUST_EXIST);
        return schema::encode([
            'courseid' => $course->id, 'fullname' => $course->fullname,
            'shortname' => $course->shortname, 'idnumber' => $course->idnumber,
            'author' => fullname($author), 'templateid' => $template->templateid,
            'templateversion' => $template->revision,
            'templatename' => $DB->get_field('local_swf_template', 'name', ['id' => $template->templateid]),
            'institution' => schema::decode($template->identityjson),
        ]);
    }
    private static function insert(int $planid, int $revision, ?int $parentid,
            \stdClass $template, array $payload, int $actorid): \stdClass {
        global $DB;
        $plan = syllabus_repository::plan($planid);
        $now = time();
        $encoded = schema::encode($payload);
        $record = (object)[
            'planid' => $planid, 'revision' => $revision, 'parentid' => $parentid,
            'templateversionid' => $template->id, 'payload' => $encoded,
            'snapshot' => self::snapshot($plan, $template),
            'contenthash' => hash('sha256', $encoded),
            'status' => workflow_state_machine::DRAFT, 'lockversion' => 0,
            'createdby' => $actorid, 'timecreated' => $now, 'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('local_swf_version', $record);
        return $record;
    }
    public static function locked(int $planid, callable $callback) {
        $factory = \core\lock\lock_config::get_lock_factory('local_syllabusworkflow');
        $lock = $factory->get_lock('plan_' . $planid, 20);
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'local_syllabusworkflow');
        }
        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }
    public static function save(int $planid, int $versionid, array $fields, int $lockversion, int $actorid): void {
        global $DB;
        self::locked($planid, function() use ($DB, $planid, $versionid, $fields, $lockversion, $actorid) {
            $transaction = $DB->start_delegated_transaction();
            $plan = syllabus_repository::plan($planid);
            $version = syllabus_repository::version($versionid);
            transition_validator::require($plan, $version, $actorid, 'revise');
            if ($version->status !== workflow_state_machine::DRAFT ||
                    (int)$version->lockversion !== $lockversion) {
                throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
            }
            $template = syllabus_repository::template($version->templateversionid);
            $definition = schema::decode($template->schemajson);
            $previous = schema::decode($version->payload);
            // Draft save deliberately permits incomplete required fields; submit validates them.
            $relaxed = array_map(static function($field) {
                $field['required'] = false;
                return $field;
            }, $definition);
            $payload = schema::validate_payload($fields, $relaxed, $previous);
            $encoded = schema::encode($payload);
            $DB->update_record('local_swf_version', (object)[
                'id' => $versionid, 'payload' => $encoded,
                'contenthash' => hash('sha256', $encoded),
                'lockversion' => $version->lockversion + 1, 'timemodified' => time(),
            ]);
            $DB->set_field('local_swf_plan', 'timemodified', time(), ['id' => $planid]);
            audit_history::log($version, 'draft_saved', $actorid);
            $transaction->allow_commit();
        });
    }
    public static function fork(int $planid, int $actorid, ?int $expectedversionid = null): \stdClass {
        global $DB;
        return self::locked($planid, function() use ($DB, $planid, $actorid, $expectedversionid) {
            $transaction = $DB->start_delegated_transaction();
            $plan = syllabus_repository::plan($planid);
            $source = syllabus_repository::version($plan->currentversionid);
            if ($expectedversionid !== null && $source->id != $expectedversionid) {
                throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
            }
            transition_validator::require($plan, $source, $actorid, 'revise');
            if (!in_array($source->status, [workflow_state_machine::CHANGES_REQUESTED,
                    workflow_state_machine::APPROVED, workflow_state_machine::PUBLISHED], true)) {
                throw new \moodle_exception('invalidrevision', 'local_syllabusworkflow');
            }
            $template = syllabus_repository::template($source->templateversionid);
            $revision = self::insert($planid, $source->revision + 1, $source->id,
                $template, schema::decode($source->payload), $actorid);
            $DB->update_record('local_swf_plan', (object)[
                'id' => $planid, 'currentversionid' => $revision->id, 'timemodified' => time(),
            ]);
            audit_history::log($revision, 'forked', $actorid, (string)$source->id);
            $transaction->allow_commit();
            return $revision;
        });
    }
}
