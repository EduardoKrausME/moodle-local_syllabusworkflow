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
 * Institutional template manager for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Institutional template manager implementation.
 */
final class institutional_template_manager {
    /**
     * Ensure default.
     *
     * @param int $actorid actorid
     * @return \stdClass
     */
    public static function ensure_default(int $actorid): \stdClass {
        global $DB;
        $current = $DB->get_record('local_syllabusworkflow_template', ['enabled' => 1], '*', IGNORE_MULTIPLE);
        if ($current && $current->activeversionid) {
            return $DB->get_record('local_syllabusworkflow_tplversion', ['id' => $current->activeversionid], '*', MUST_EXIST);
        }
        $factory = \core\lock\lock_config::get_lock_factory('local_syllabusworkflow');
        $lock = $factory->get_lock('default_template', 10);
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'local_syllabusworkflow');
        }
        try {
            $current = $DB->get_record('local_syllabusworkflow_template', ['enabled' => 1], '*', IGNORE_MULTIPLE);
            if ($current && $current->activeversionid) {
                return $DB->get_record('local_syllabusworkflow_tplversion', ['id' => $current->activeversionid], '*', MUST_EXIST);
            }
            return self::create(get_string('defaulttemplate', 'local_syllabusworkflow'), schema::defaults(),
                [['reviewerid' => 0, 'days' => 7]], [], $actorid);
        } finally {
            $lock->release();
        }
    }
    /**
     * Create.
     *
     * @param string $name name
     * @param array $fields fields
     * @param array $stages stages
     * @param array $identity identity
     * @param int $actorid actorid
     * @return \stdClass
     */
    public static function create(string $name, array $fields, array $stages, array $identity, int $actorid): \stdClass {
        global $DB;
        schema::validate_definition($fields);
        if (trim($name) === '') {
            throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
        }
        self::validate_stages($stages);
        $transaction = $DB->start_delegated_transaction();
        $now = time();
        $templateid = $DB->insert_record('local_syllabusworkflow_template', (object)[
            'name' => clean_param($name, PARAM_TEXT), 'enabled' => 1,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $version = self::insert_version($templateid, 1, $fields, $stages, $identity, $actorid);
        $DB->set_field('local_syllabusworkflow_template', 'activeversionid', $version->id, ['id' => $templateid]);
        $transaction->allow_commit();
        return $version;
    }
    /**
     * Revise.
     *
     * @param int $templateid templateid
     * @param array $fields fields
     * @param array $stages stages
     * @param array $identity identity
     * @param int $actorid actorid
     * @return \stdClass
     */
    public static function revise(int $templateid, array $fields, array $stages, array $identity,
            int $actorid): \stdClass {
        global $DB;
        schema::validate_definition($fields);
        self::validate_stages($stages);
        $factory = \core\lock\lock_config::get_lock_factory('local_syllabusworkflow');
        $lock = $factory->get_lock('template_' . $templateid, 10);
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'local_syllabusworkflow');
        }
        try {
            $transaction = $DB->start_delegated_transaction();
            $template = $DB->get_record('local_syllabusworkflow_template', ['id' => $templateid], '*', MUST_EXIST);
            $previous = $DB->get_record('local_syllabusworkflow_tplversion', ['id' => $template->activeversionid], '*', MUST_EXIST);
            $version = self::insert_version($templateid, $previous->revision + 1, $fields, $stages, $identity, $actorid);
            $DB->update_record('local_syllabusworkflow_template', (object)[
                'id' => $templateid, 'activeversionid' => $version->id, 'timemodified' => time(),
            ]);
            $transaction->allow_commit();
            return $version;
        } finally {
            $lock->release();
        }
    }
    /**
     * Validate stages.
     *
     * @param array $stages stages
     * @return void
     */
    private static function validate_stages(array $stages): void {
        if (!$stages || count($stages) > 10) {
            throw new \moodle_exception('invalidstages', 'local_syllabusworkflow');
        }
        foreach ($stages as $index => $stage) {
            if (!is_array($stage) || !isset($stage['reviewerid'], $stage['days'])) {
                throw new \moodle_exception('invalidstages', 'local_syllabusworkflow');
            }
            // Moodle's DML returns integer IDs as either integers or numeric strings.
            foreach (['reviewerid', 'days'] as $numberfield) {
                if ((!is_int($stage[$numberfield]) && !is_string($stage[$numberfield])) ||
                        !preg_match('/^(0|[1-9][0-9]*)$/D', (string)$stage[$numberfield])) {
                    throw new \moodle_exception('invalidstages', 'local_syllabusworkflow');
                }
            }
            $reviewerid = (int)$stage['reviewerid'];
            $days = (int)$stage['days'];
            if ($days > 365 || $reviewerid < 0 || ($index > 0 && $reviewerid === 0)) {
                throw new \moodle_exception('invalidstages', 'local_syllabusworkflow');
            }
        }
    }
    /**
     * Insert version.
     *
     * @param int $templateid templateid
     * @param int $revision revision
     * @param array $fields fields
     * @param array $stages stages
     * @param array $identity identity
     * @param int $actorid actorid
     * @return \stdClass
     */
    private static function insert_version(int $templateid, int $revision, array $fields,
            array $stages, array $identity, int $actorid): \stdClass {
        global $DB;
        $schemajson = schema::encode($fields);
        $workflowjson = schema::encode($stages);
        $identityjson = schema::encode($identity);
        $record = (object)[
            'templateid' => $templateid, 'revision' => $revision,
            'schemajson' => $schemajson, 'workflowjson' => $workflowjson,
            'identityjson' => $identityjson,
            'contenthash' => hash('sha256', $schemajson . $workflowjson . $identityjson),
            'createdby' => $actorid, 'timecreated' => time(),
        ];
        $record->id = $DB->insert_record('local_syllabusworkflow_tplversion', $record);
        return $record;
    }
}
