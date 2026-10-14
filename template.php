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
 * Template for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();
$context = \context_system::instance();
require_capability('local/syllabusworkflow:manage', $context);
$templateid = optional_param('templateid', 0, PARAM_INT);
$version = null;
if ($templateid) {
    $existing = $DB->get_record('local_syllabusworkflow_template', ['id' => $templateid], '*', MUST_EXIST);
    $version = \local_syllabusworkflow\local\syllabus_repository::template($existing->activeversionid);
}
$PAGE->set_url('/local/syllabusworkflow/template.php', ['templateid' => $templateid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('models', 'local_syllabusworkflow'));
$PAGE->set_heading(get_string('models', 'local_syllabusworkflow'));
$form = new \local_syllabusworkflow\form\template_form();
if ($form->is_cancelled()) {
    redirect(new \moodle_url('/local/syllabusworkflow/index.php'));
}
if ($data = $form->get_data()) {
    require_sesskey();
    try {
        $fields = \local_syllabusworkflow\local\schema::decode($data->schemajson);
        $stages = \local_syllabusworkflow\local\schema::decode($data->workflowjson);
        $identity = \local_syllabusworkflow\local\schema::decode($data->identityjson);
    } catch (\JsonException $e) {
        throw new \moodle_exception('invalidschema', 'local_syllabusworkflow', '', null, $e->getMessage());
    }
    if ($templateid) {
        \local_syllabusworkflow\local\institutional_template_manager::revise(
            $templateid, $fields, $stages, $identity, $USER->id);
    } else {
        \local_syllabusworkflow\local\institutional_template_manager::create(
            $data->name, $fields, $stages, $identity, $USER->id);
    }
    redirect(new \moodle_url('/local/syllabusworkflow/template.php'),
        get_string('saved', 'local_syllabusworkflow'));
}
$defaults = ['templateid' => $templateid,
    'name' => $templateid ? $existing->name : '',
    'schemajson' => $version ? $version->schemajson : json_encode(
        \local_syllabusworkflow\local\schema::defaults(), JSON_PRETTY_PRINT),
    'workflowjson' => $version ? $version->workflowjson : '[{"reviewerid":0,"days":7}]',
    'identityjson' => $version ? $version->identityjson : '{}'];
$form->set_data((object)$defaults);
$templates = [];
foreach ($DB->get_records('local_syllabusworkflow_template', [], 'name ASC') as $model) {
    $templates[] = [
        'name' => $model->name,
        'url' => (new \moodle_url('/local/syllabusworkflow/template.php',
            ['templateid' => $model->id]))->out(false),
        'versionid' => $model->activeversionid,
    ];
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_syllabusworkflow/models', [
    'title' => get_string('models', 'local_syllabusworkflow'),
    'templates' => $templates, 'labelrevision' => get_string('revision', 'local_syllabusworkflow'),
]);
$form->display();
echo $OUTPUT->footer();
