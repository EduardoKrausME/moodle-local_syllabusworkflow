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
 * Edit for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$id = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
if ($id) {
    $plan = \local_syllabusworkflow\local\syllabus_repository::plan($id);
    $courseid = $plan->courseid;
}
$course = get_course($courseid);
require_login($course);
$context = \context_course::instance($courseid);
require_capability('local/syllabusworkflow:edit', $context);
if ($id) {
    $version = \local_syllabusworkflow\local\syllabus_repository::version($plan->currentversionid);
    if ($version->status !== \local_syllabusworkflow\local\workflow_state_machine::DRAFT) {
        throw new \moodle_exception('readonlyversion', 'local_syllabusworkflow');
    }
    $template = \local_syllabusworkflow\local\syllabus_repository::template($version->templateversionid);
    $initial = \local_syllabusworkflow\local\schema::decode($version->payload);
} else {
    $templateid = optional_param('templateversionid', 0, PARAM_INT);
    if ($templateid) {
        if (!$DB->record_exists('local_syllabusworkflow_template', ['activeversionid' => $templateid, 'enabled' => 1])) {
            throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
        }
        $template = \local_syllabusworkflow\local\syllabus_repository::template($templateid);
    } else {
        $template = \local_syllabusworkflow\local\institutional_template_manager::ensure_default($USER->id);
    }
    $initial = [];
}
$fields = \local_syllabusworkflow\local\schema::decode($template->schemajson);
$PAGE->set_url('/local/syllabusworkflow/edit.php', $id ? ['id' => $id] : ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('editplan', 'local_syllabusworkflow'));
$PAGE->set_heading(get_string('editplan', 'local_syllabusworkflow'));
$formurl = new \moodle_url('/local/syllabusworkflow/edit.php', [
    'courseid' => $courseid, 'id' => $id,
]);
if (!$id && !empty($templateid)) {
    $formurl->param('templateversionid', $templateid);
}
$form = new \local_syllabusworkflow\form\plan_form($formurl, ['fields' => $fields]);
if ($form->is_cancelled()) {
    redirect(new \moodle_url('/local/syllabusworkflow/index.php', ['courseid' => $courseid]));
}
if ($data = $form->get_data()) {
    require_sesskey();
    $input = [];
    foreach ($fields as $field) {
        $input[$field['key']] = !empty($field['locked']) && $id ? ($initial[$field['key']] ?? '') :
            ($data->{'f_' . $field['key']} ?? '');
    }
    if (!$id) {
        $version = \local_syllabusworkflow\local\version_manager::create_plan($courseid, $template->id, $USER->id);
        $id = $version->planid;
    } else if ((int)$data->versionid !== (int)$version->id) {
        throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
    }
    \local_syllabusworkflow\local\version_manager::save($id, $version->id,
        $input, (int)$data->lockversion, $USER->id);
    redirect(new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $id]),
        get_string('saved', 'local_syllabusworkflow'));
}
$defaults = ['id' => $id, 'versionid' => $id ? $version->id : 0,
    'lockversion' => $id ? $version->lockversion : 0];
foreach ($fields as $field) {
    $defaults['f_' . $field['key']] = $initial[$field['key']] ?? '';
}
$form->set_data((object)$defaults);
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
