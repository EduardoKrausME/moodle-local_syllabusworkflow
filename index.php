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
 * Index for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid) {
    $course = get_course($courseid);
    require_login($course);
    $context = \context_course::instance($courseid);
    if (!has_capability('local/syllabusworkflow:view', $context) &&
            !has_capability('local/syllabusworkflow:edit', $context)) {
        require_capability('local/syllabusworkflow:view', $context);
    }
    $plans = \local_syllabusworkflow\local\course_integration::plans($courseid);
} else {
    require_login();
    $context = \context_system::instance();
    require_capability('local/syllabusworkflow:manage', $context);
    $plans = $DB->get_records('local_syllabusworkflow_plan', [], 'timemodified DESC', '*', 0, 200);
}
$PAGE->set_url('/local/syllabusworkflow/index.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_syllabusworkflow'));
$PAGE->set_heading(get_string('pluginname', 'local_syllabusworkflow'));
$rows = [];
foreach ($plans as $plan) {
    $version = \local_syllabusworkflow\local\syllabus_repository::version($plan->currentversionid);
    $mayedit = has_capability('local/syllabusworkflow:edit', \context_course::instance($plan->courseid));
    $mayview = $mayedit || has_capability('local/syllabusworkflow:review', \context_course::instance($plan->courseid)) ||
        (has_capability('local/syllabusworkflow:view', \context_course::instance($plan->courseid)) &&
        (bool)$plan->publishedversionid);
    if (!$mayview) {
        continue;
    }
    $rows[] = [
        'id' => $plan->id, 'courseid' => $plan->courseid,
        'revision' => $version->revision,
        'state' => get_string('state_' . $version->status, 'local_syllabusworkflow'),
        'url' => (new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $plan->id]))->out(false),
    ];
}
$canedit = $courseid && has_capability('local/syllabusworkflow:edit', $context);
$canmanage = has_capability('local/syllabusworkflow:manage', \context_system::instance());
$modelchoices = [];
if ($canedit) {
    foreach ($DB->get_records('local_syllabusworkflow_template', ['enabled' => 1], 'name ASC') as $model) {
        $modelchoices[] = [
            'name' => $model->name,
            'url' => (new \moodle_url('/local/syllabusworkflow/edit.php', [
                'courseid' => $courseid, 'templateversionid' => $model->activeversionid,
            ]))->out(false),
        ];
    }
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_syllabusworkflow/dashboard', [
    'title' => get_string('pluginname', 'local_syllabusworkflow'),
    'plans' => array_values($rows), 'modelchoices' => $modelchoices,
    'labelcourse' => get_string('course'),
    'labelrevision' => get_string('revision', 'local_syllabusworkflow'),
    'labelstatus' => get_string('status', 'local_syllabusworkflow'),
    'labelview' => get_string('viewplan', 'local_syllabusworkflow'),
    'canedit' => $canedit, 'createurl' => (new \moodle_url('/local/syllabusworkflow/edit.php',
        ['courseid' => $courseid]))->out(false),
    'createlabel' => get_string('createplan', 'local_syllabusworkflow'),
    'canmanage' => $canmanage,
    'templatesurl' => (new \moodle_url('/local/syllabusworkflow/template.php'))->out(false),
    'reporturl' => (new \moodle_url('/local/syllabusworkflow/report.php'))->out(false),
    'templateslabel' => get_string('models', 'local_syllabusworkflow'),
    'reportlabel' => get_string('reports', 'local_syllabusworkflow'),
]);
echo $OUTPUT->footer();
