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
 * Export for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$id = required_param('id', PARAM_INT);
$versionid = optional_param('versionid', 0, PARAM_INT);
$plan = \local_syllabusworkflow\local\syllabus_repository::plan($id);
require_login(get_course($plan->courseid));
$versionid = $versionid ?: (int)$plan->publishedversionid;
if (!$versionid) {
    throw new \moodle_exception('notpublished', 'local_syllabusworkflow');
}
$version = \local_syllabusworkflow\local\syllabus_repository::readable_version($plan, $versionid, $USER->id);
$template = \local_syllabusworkflow\local\syllabus_repository::template($version->templateversionid);
$contents = \local_syllabusworkflow\local\schema::decode($version->payload);
$snapshot = \local_syllabusworkflow\local\schema::decode($version->snapshot);
$fields = [];
foreach (\local_syllabusworkflow\local\schema::decode($template->schemajson) as $definition) {
    $fields[] = [
        'label' => get_string($definition['key'], 'local_syllabusworkflow'),
        'value' => $contents[$definition['key']] ?? '',
    ];
}
$context = \context_course::instance($plan->courseid);
$PAGE->set_url('/local/syllabusworkflow/export.php', ['id' => $id, 'versionid' => $versionid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('export', 'local_syllabusworkflow'));
$PAGE->set_heading(get_string('export', 'local_syllabusworkflow'));
$data = [
    'title' => get_string('pluginname', 'local_syllabusworkflow'),
    'course' => $snapshot['fullname'], 'author' => $snapshot['author'],
    'revision' => $version->revision, 'hash' => $version->contenthash,
    'fields' => $fields,
];
if (optional_param('download', 0, PARAM_BOOL)) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="syllabus-' . $id . '-r' . $version->revision . '.html"');
    echo $OUTPUT->render_from_template('local_syllabusworkflow/export', $data);
    exit;
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_syllabusworkflow/export', $data);
echo $OUTPUT->footer();
