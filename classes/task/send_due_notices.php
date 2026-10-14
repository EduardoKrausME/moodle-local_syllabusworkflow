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
 * Send due notices for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\task;
defined('MOODLE_INTERNAL') || die();
final class send_due_notices extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('taskduedates', 'local_syllabusworkflow');
    }
    public function execute(): void {
        global $DB;
        $today = strtotime('today');
        $end = $today + 3 * DAYSECS;
        $sql = "SELECT a.* FROM {local_swf_assignment} a
                 JOIN {local_swf_version} v ON v.id = a.versionid
                WHERE a.status = :status AND a.duedate > 0
                  AND a.duedate < :deadline
                  AND v.status IN (:submitted, :review)";
        $params = ['status' => 'pending', 'deadline' => $end,
            'submitted' => 'submitted', 'review' => 'under_review'];
        $rs = $DB->get_recordset_sql($sql, $params);
        try {
            foreach ($rs as $assignment) {
                $kind = $assignment->duedate < $today ? 'late' : 'soon';
                \local_syllabusworkflow\local\notification_service::send(
                    $assignment->versionid, $assignment->reviewerid,
                    'duedate', $assignment->id . ':' . $kind);
            }
        } finally {
            $rs->close();
        }
    }
}
