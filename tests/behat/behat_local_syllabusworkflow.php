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
 * Behat local syllabusworkflow for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Behat steps for creating and browsing syllabus plans.
 */
class behat_local_syllabusworkflow extends behat_base {
    /**
     * Create a documented state using the public workflow services.
     *
     * @Given /^a syllabus of "([^"]+)" for course "([^"]+)" exists in state "([^"]+)"$/
     */
    public function a_syllabus_exists_in_state(string $username, string $shortname, string $state): void {
        global $DB;
        $author = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
        $reviewer = get_admin();
        $template = \local_syllabusworkflow\local\institutional_template_manager::ensure_default($reviewer->id);
        $version = \local_syllabusworkflow\local\version_manager::create_plan($course->id, $template->id, $author->id);
        $fields = array_fill_keys(array_column(\local_syllabusworkflow\local\schema::defaults(), 'key'), 'Sample syllabus content');
        \local_syllabusworkflow\local\version_manager::save($version->planid, $version->id, $fields, 0, $author->id);
        if ($state === 'draft') {
            return;
        }
        \local_syllabusworkflow\local\approval_service::submit($version->planid, $reviewer->id, $author->id);
        if ($state === 'submitted') {
            return;
        }
        \local_syllabusworkflow\local\approval_service::begin($version->planid, $reviewer->id);
        if ($state === 'under_review') {
            return;
        }
        if ($state === 'changes_requested') {
            \local_syllabusworkflow\local\approval_service::decide(
                $version->planid, $reviewer->id, false, 'Please revise the objectives');
            return;
        }
        \local_syllabusworkflow\local\approval_service::decide($version->planid, $reviewer->id, true, 'Approved');
        if ($state === 'approved') {
            return;
        }
        if ($state === 'published') {
            \local_syllabusworkflow\local\publication_service::publish($version->planid, $reviewer->id);
            return;
        }
        throw new \coding_exception('Unsupported Behat syllabus fixture status');
    }
    /**
     * Open the most recently created plan in this course.
     *
     * @When /^I open the syllabus for course "([^"]+)"$/
     */
    public function i_open_syllabus_for_course(string $shortname): void {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
        $plans = $DB->get_records('local_syllabusworkflow_plan', ['courseid' => $course->id], 'id DESC', '*', 0, 1);
        $plan = reset($plans);
        if (!$plan) {
            throw new \moodle_exception('noplan', 'local_syllabusworkflow');
        }
        $url = new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $plan->id]);
        $this->getSession()->visit($url->out(false));
    }
}
