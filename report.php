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
 * Report for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();
$context = \context_system::instance();
require_capability('local/syllabusworkflow:manage', $context);
$PAGE->set_url('/local/syllabusworkflow/report.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('reports', 'local_syllabusworkflow'));
$PAGE->set_heading(get_string('reports', 'local_syllabusworkflow'));
$sql = 'SELECT p.id, p.courseid, v.revision, v.status, v.timemodified
          FROM {local_syllabusworkflow_plan} p JOIN {local_syllabusworkflow_version} v ON v.id = p.currentversionid
      ORDER BY v.timemodified DESC';
$pending = [];
$approved = [];
foreach ($DB->get_records_sql($sql) as $record) {
    $row = [
        'courseid' => $record->courseid, 'revision' => $record->revision,
        'status' => get_string('state_' . $record->status, 'local_syllabusworkflow'),
        'date' => userdate($record->timemodified),
        'url' => (new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $record->id]))->out(false),
    ];
    if (in_array($record->status, ['approved', 'published'], true)) {
        $approved[] = $row;
    } else {
        $pending[] = $row;
    }
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_syllabusworkflow/reports', [
    'pending' => $pending, 'approved' => $approved,
    'labelpending' => get_string('pending', 'local_syllabusworkflow'),
    'labelapproved' => get_string('approvedplans', 'local_syllabusworkflow'),
]);
echo $OUTPUT->footer();
