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
 * View for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$id = required_param('id', PARAM_INT);
$requested = optional_param('versionid', 0, PARAM_INT);
$plan = \local_syllabusworkflow\local\syllabus_repository::plan($id);
$course = get_course($plan->courseid);
require_login($course);
$context = \context_course::instance($plan->courseid);
$privileged = has_capability('local/syllabusworkflow:edit', $context) ||
    has_capability('local/syllabusworkflow:review', $context) ||
    has_capability('local/syllabusworkflow:approve', $context) ||
    has_capability('local/syllabusworkflow:publish', $context);
$selected = $requested ?: ($privileged ? $plan->currentversionid : $plan->publishedversionid);
if (!$selected) {
    throw new \moodle_exception('notpublished', 'local_syllabusworkflow');
}
$version = \local_syllabusworkflow\local\syllabus_repository::readable_version($plan, $selected, $USER->id);
$payload = \local_syllabusworkflow\local\schema::decode($version->payload);
$snapshot = \local_syllabusworkflow\local\schema::decode($version->snapshot);
$template = \local_syllabusworkflow\local\syllabus_repository::template($version->templateversionid);
$definition = \local_syllabusworkflow\local\schema::decode($template->schemajson);
$fields = [];
foreach ($definition as $field) {
    $fields[] = ['label' => get_string($field['key'], 'local_syllabusworkflow'),
        'value' => $payload[$field['key']] ?? ''];
}
$versions = [];
if ($privileged) {
    foreach ($DB->get_records('local_swf_version', ['planid' => $id], 'revision DESC') as $old) {
        $versions[] = ['revision' => $old->revision,
            'status' => get_string('state_' . $old->status, 'local_syllabusworkflow'),
            'url' => (new \moodle_url('/local/syllabusworkflow/view.php',
                ['id' => $id, 'versionid' => $old->id]))->out(false)];
    }
}
$transitions = [];
$approvals = [];
if ($privileged) {
    foreach ($DB->get_records('local_swf_approval', ['versionid' => $version->id], 'id ASC') as $decision) {
        $approvals[] = [
            'actor' => $decision->reviewerid, 'stage' => $decision->stage + 1,
            'decision' => $decision->decision, 'comment' => $decision->comment,
            'date' => userdate($decision->timecreated),
        ];
    }
    foreach ($DB->get_records('local_swf_transition', ['versionid' => $version->id], 'id ASC') as $change) {
        $transitions[] = ['from' => $change->fromstate, 'to' => $change->tostate,
            'actor' => $change->actorid, 'date' => userdate($change->timecreated),
            'comment' => $change->comment, 'operationid' => $change->operationid];
    }
}
$PAGE->set_url('/local/syllabusworkflow/view.php', ['id' => $id, 'versionid' => $selected]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('viewplan', 'local_syllabusworkflow'));
$PAGE->set_heading(get_string('viewplan', 'local_syllabusworkflow'));
$PAGE->requires->js_call_amd('local_syllabusworkflow/copylink', 'init');
$actionurls = [];
foreach (['submit', 'review', 'approve', 'changes', 'publish', 'revise', 'delegate', 'archive'] as $action) {
    $actionurls[$action . 'url'] = (new \moodle_url('/local/syllabusworkflow/action.php',
        ['id' => $id, 'action' => $action]))->out(false);
}
$common = $actionurls + [
    'id' => $id, 'revision' => $version->revision, 'hash' => $version->contenthash,
    'course' => $snapshot['fullname'], 'author' => $snapshot['author'],
    'status' => get_string('state_' . $version->status, 'local_syllabusworkflow'),
    'fields' => $fields, 'versions' => $versions, 'transitions' => $transitions, 'approvals' => $approvals,
    'labelrevision' => get_string('revision', 'local_syllabusworkflow'),
    'labelstatus' => get_string('status', 'local_syllabusworkflow'),
    'labelhash' => get_string('hash', 'local_syllabusworkflow'),
    'labelauthor' => get_string('author', 'local_syllabusworkflow'),
    'labelhistory' => get_string('history', 'local_syllabusworkflow'),
    'labeltransitions' => get_string('transitions', 'local_syllabusworkflow'),
    'labelcompare' => get_string('compare', 'local_syllabusworkflow'),
    'compareurl' => (new \moodle_url('/local/syllabusworkflow/compare.php', ['id' => $id]))->out(false),
    'exporturl' => (new \moodle_url('/local/syllabusworkflow/export.php',
        ['id' => $id, 'versionid' => $selected]))->out(false),
    'labelexport' => get_string('export', 'local_syllabusworkflow'),
    'labeldownload' => get_string('downloadhtml', 'local_syllabusworkflow'),
    'downloadurl' => (new \moodle_url('/local/syllabusworkflow/export.php',
        ['id' => $id, 'versionid' => $selected, 'download' => 1]))->out(false),
    'labelcopy' => get_string('copylink', 'local_syllabusworkflow'),
    'publicurl' => (new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $id]))->out(false),
    'privileged' => $privileged,
    'canedit' => $privileged && $version->status === 'draft' &&
        has_capability('local/syllabusworkflow:edit', $context),
    'edurl' => (new \moodle_url('/local/syllabusworkflow/edit.php', ['id' => $id]))->out(false),
    'labeledit' => get_string('editplan', 'local_syllabusworkflow'),
    'cansubmit' => $privileged && $selected == $plan->currentversionid &&
        $version->status === 'draft' && has_capability('local/syllabusworkflow:edit', $context),
    'canreview' => $privileged && $selected == $plan->currentversionid &&
        $version->status === 'submitted' && has_capability('local/syllabusworkflow:review', $context),
    'candecide' => $privileged && $selected == $plan->currentversionid &&
        $version->status === 'under_review' && has_capability('local/syllabusworkflow:approve', $context),
    'canpublish' => $privileged && $selected == $plan->currentversionid &&
        $version->status === 'approved' && has_capability('local/syllabusworkflow:publish', $context),
    'canrevise' => $privileged && $selected == $plan->currentversionid &&
        in_array($version->status, ['changes_requested', 'approved', 'published'], true) &&
        has_capability('local/syllabusworkflow:edit', $context),
    'candelegate' => has_capability('local/syllabusworkflow:manage', \context_system::instance()) &&
        in_array($version->status, ['submitted', 'under_review'], true),
    'canarchive' => $selected == $plan->currentversionid &&
        has_capability('local/syllabusworkflow:manage', \context_system::instance()) &&
        in_array($version->status, ['draft', 'changes_requested', 'approved', 'published'], true),
    'labelarchive' => get_string('archive', 'local_syllabusworkflow'),
    'labelsubmit' => get_string('submit', 'local_syllabusworkflow'),
    'labelreview' => get_string('review', 'local_syllabusworkflow'),
    'labelapprove' => get_string('approve', 'local_syllabusworkflow'),
    'labelchanges' => get_string('changes', 'local_syllabusworkflow'),
    'labelpublish' => get_string('publish', 'local_syllabusworkflow'),
    'labelrevise' => get_string('newrevision', 'local_syllabusworkflow'),
    'labeldelegate' => get_string('delegate', 'local_syllabusworkflow'),
    'labelreviewerid' => get_string('reviewerid', 'local_syllabusworkflow'),
    'labelcomment' => get_string('comment', 'local_syllabusworkflow'),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_syllabusworkflow/plan', $common);
echo $OUTPUT->footer();
