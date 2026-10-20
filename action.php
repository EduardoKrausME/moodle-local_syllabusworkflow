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
 * Action for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$validactions = ['submit', 'review', 'approve', 'changes', 'publish', 'revise', 'delegate', 'archive'];
if (!in_array($action, $validactions, true)) {
    throw new \moodle_exception('invalidaction', 'local_syllabusworkflow');
}
$plan = \local_syllabusworkflow\local\syllabus_repository::plan($id);
$course = get_course($plan->courseid);
require_login($course);
$context = \context_course::instance($course->id);
if (in_array($action, ['delegate', 'archive'], true)) {
    require_capability('local/syllabusworkflow:manage', \context_system::instance());
} else {
    require_capability(\local_syllabusworkflow\local\transition_validator::capability($action), $context);
}
$version = \local_syllabusworkflow\local\syllabus_repository::version($plan->currentversionid);
if ($action === 'archive') {
    \local_syllabusworkflow\local\workflow_state_machine::assert(
        $version->status, \local_syllabusworkflow\local\workflow_state_machine::ARCHIVED);
}
$reviewers = [];
if (in_array($action, ['submit', 'delegate'], true)) {
    $all = get_users_by_capability($context, 'local/syllabusworkflow:approve',
        'u.*', 'u.firstname ASC, u.lastname ASC');
    foreach ($all as $user) {
        $reviewers[$user->id] = fullname($user);
    }
    $admin = get_admin();
    if ($admin && has_capability('local/syllabusworkflow:approve', $context, $admin)) {
        $reviewers[$admin->id] = fullname($admin);
    }
    if (!$reviewers) {
        throw new \moodle_exception('invalidreviewer', 'local_syllabusworkflow');
    }
}
$PAGE->set_url('/local/syllabusworkflow/action.php', ['id' => $id, 'action' => $action]);
$PAGE->set_context($context);
$PAGE->set_title(get_string($action === 'revise' ? 'newrevision' : $action, 'local_syllabusworkflow'));
$PAGE->set_heading(get_string($action === 'revise' ? 'newrevision' : $action, 'local_syllabusworkflow'));
$form = new \local_syllabusworkflow\form\action_form(null, [
    'id' => $id, 'versionid' => $version->id,
    'action' => $action, 'reviewers' => $reviewers,
]);
if ($form->is_cancelled()) {
    redirect(new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $id]));
}
if ($data = $form->get_data()) {
    require_sesskey();
    if ((int)$data->versionid !== (int)$version->id) {
        throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
    }
    switch ($action) {
        case 'submit':
            \local_syllabusworkflow\local\approval_service::submit($id, $data->reviewerid, $USER->id, $version->id);
            break;
        case 'review':
            \local_syllabusworkflow\local\approval_service::begin($id, $USER->id, $version->id);
            break;
        case 'approve':
            \local_syllabusworkflow\local\approval_service::decide($id, $USER->id, true,
                $data->comment ?? '', $version->id);
            break;
        case 'changes':
            \local_syllabusworkflow\local\approval_service::decide($id, $USER->id, false,
                $data->comment, $version->id);
            break;
        case 'publish':
            \local_syllabusworkflow\local\publication_service::publish($id, $USER->id, $version->id);
            break;
        case 'revise':
            \local_syllabusworkflow\local\version_manager::fork($id, $USER->id, $version->id);
            break;
        case 'delegate':
            \local_syllabusworkflow\local\approval_service::delegate($id, $data->reviewerid,
                $USER->id, $version->id);
            break;
        case 'archive':
            \local_syllabusworkflow\local\publication_service::archive($id, $USER->id, $version->id);
            break;
    }
    redirect(new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $id]),
        get_string('actionsaved', 'local_syllabusworkflow'));
}
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
