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
 * Compare for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$id = required_param('id', PARAM_INT);
$beforeid = optional_param('before', 0, PARAM_INT);
$afterid = optional_param('after', 0, PARAM_INT);
$plan = \local_syllabusworkflow\local\syllabus_repository::plan($id);
require_login(get_course($plan->courseid));
$context = \context_course::instance($plan->courseid);
if (!has_capability('local/syllabusworkflow:edit', $context) &&
        !has_capability('local/syllabusworkflow:review', $context)) {
    require_capability('local/syllabusworkflow:edit', $context);
}
$versions = array_values($DB->get_records('local_syllabusworkflow_version', ['planid' => $id], 'revision DESC'));
if (!$versions) {
    throw new \moodle_exception('wrongplan', 'local_syllabusworkflow');
}
$afterid = $afterid ?: $versions[0]->id;
$beforeid = $beforeid ?: ($versions[1]->id ?? $versions[0]->id);
$before = \local_syllabusworkflow\local\syllabus_repository::readable_version($plan, $beforeid, $USER->id);
$after = \local_syllabusworkflow\local\syllabus_repository::readable_version($plan, $afterid, $USER->id);
$choices = [];
foreach ($versions as $version) {
    $choices[] = ['id' => $version->id, 'revision' => $version->revision,
        'beforeselected' => $version->id == $beforeid, 'afterselected' => $version->id == $afterid];
}
$PAGE->set_url('/local/syllabusworkflow/compare.php', ['id' => $id, 'before' => $beforeid, 'after' => $afterid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('compare', 'local_syllabusworkflow'));
$PAGE->set_heading(get_string('compare', 'local_syllabusworkflow'));
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_syllabusworkflow/diff', [
    'id' => $id, 'choices' => $choices,
    'diffs' => \local_syllabusworkflow\local\version_diff_engine::compare($before, $after),
    'beforeauthor' => $before->createdby, 'afterauthor' => $after->createdby,
    'beforedate' => userdate($before->timecreated), 'afterdate' => userdate($after->timecreated),
    'labelcompare' => get_string('compare', 'local_syllabusworkflow'),
    'labelbefore' => get_string('before', 'local_syllabusworkflow'),
    'labelafter' => get_string('after', 'local_syllabusworkflow'),
    'url' => (new \moodle_url('/local/syllabusworkflow/compare.php'))->out(false),
]);
echo $OUTPUT->footer();
