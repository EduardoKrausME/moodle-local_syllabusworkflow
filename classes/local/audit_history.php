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
 * Audit history for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Audit history implementation.
 */
final class audit_history {
    /**
     * Transition.
     *
     * @param \stdClass $version version
     * @param string $from from
     * @param string $to to
     * @param int $actorid actorid
     * @param string $comment comment
     * @return void
     */
    public static function transition(\stdClass $version, string $from, string $to,
            int $actorid, string $comment): void {
        global $DB;
        workflow_state_machine::assert($from, $to);
        $DB->insert_record('local_syllabusworkflow_transition', (object)[
            'versionid' => $version->id, 'fromstate' => $from, 'tostate' => $to,
            'actorid' => $actorid, 'comment' => $comment,
            'operationid' => bin2hex(random_bytes(16)), 'timecreated' => time(),
        ]);
        \local_syllabusworkflow\event\syllabus_changed::create([
            'context' => \context_course::instance(syllabus_repository::plan($version->planid)->courseid),
            'objectid' => $version->id,
            'relateduserid' => $version->createdby,
            'other' => ['planid' => $version->planid, 'action' => $to],
        ])->trigger();
    }
    /**
     * Log.
     *
     * @param \stdClass $version version
     * @param string $action action
     * @param int $actorid actorid
     * @param string $comment comment
     * @return void
     */
    public static function log(\stdClass $version, string $action, int $actorid, string $comment = ''): void {
        global $DB;
        $DB->insert_record('local_syllabusworkflow_audit', (object)[
            'versionid' => $version->id, 'action' => $action,
            'actorid' => $actorid, 'comment' => $comment,
            'operationid' => bin2hex(random_bytes(16)), 'timecreated' => time(),
        ]);
    }
}
