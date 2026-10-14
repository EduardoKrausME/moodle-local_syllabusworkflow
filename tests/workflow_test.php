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
 * Workflow test for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow;
defined('MOODLE_INTERNAL') || die();
use local_syllabusworkflow\local\schema;
use local_syllabusworkflow\local\institutional_template_manager;
use local_syllabusworkflow\local\version_manager;
use local_syllabusworkflow\local\approval_service;
use local_syllabusworkflow\local\publication_service;
use local_syllabusworkflow\local\syllabus_repository;
use local_syllabusworkflow\local\workflow_state_machine;
use local_syllabusworkflow\local\version_diff_engine;
/** @coversDefaultClass \local_syllabusworkflow\local\workflow_state_machine */
final class workflow_test extends \advanced_testcase {
    private function fixtures(): array {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($manager->id, $course->id, 'manager');
        $template = institutional_template_manager::create('Course template', schema::defaults(),
            [['reviewerid' => $manager->id, 'days' => 7]], [], $manager->id);
        $version = version_manager::create_plan($course->id, $template->id, $teacher->id);
        return [$course, $teacher, $manager, $template, $version];
    }
    private function complete(array $tuple): void {
        [$course, $teacher, $manager, $template, $version] = $tuple;
        $fields = array_fill_keys(array_column(schema::defaults(), 'key'), 'Valid example');
        version_manager::save($version->planid, $version->id, $fields, 0, $teacher->id);
    }
    public function test_valid_and_invalid_states(): void {
        workflow_state_machine::assert('draft', 'submitted');
        $this->expectException(\moodle_exception::class);
        workflow_state_machine::assert('draft', 'published');
    }
    public function test_required_field_validation(): void {
        $this->expectException(\moodle_exception::class);
        schema::validate_payload([], schema::defaults());
    }
    public function test_create_plan_and_revision(): void {
        $tuple = $this->fixtures();
        $this->complete($tuple);
        [$course, $teacher, $manager, $template, $version] = $tuple;
        approval_service::submit($version->planid, $manager->id, $teacher->id);
        approval_service::begin($version->planid, $manager->id);
        approval_service::decide($version->planid, $manager->id, false, 'Revise outcomes');
        $next = version_manager::fork($version->planid, $teacher->id);
        $this->assertSame(2, (int)$next->revision);
        $this->assertSame((int)$version->id, (int)$next->parentid);
        $this->assertSame('changes_requested', syllabus_repository::version($version->id)->status);
    }
    public function test_published_content_cannot_be_edited_and_publication_pointer_is_correct(): void {
        $tuple = $this->fixtures();
        $this->complete($tuple);
        [$course, $teacher, $manager, $template, $version] = $tuple;
        approval_service::submit($version->planid, $manager->id, $teacher->id);
        approval_service::begin($version->planid, $manager->id);
        approval_service::decide($version->planid, $manager->id, true, 'Approved');
        publication_service::publish($version->planid, $manager->id);
        $plan = syllabus_repository::plan($version->planid);
        $this->assertEquals($version->id, $plan->publishedversionid);
        $this->assertEquals('published', syllabus_repository::version($version->id)->status);
        $this->expectException(\moodle_exception::class);
        version_manager::save($version->planid, $version->id, [], 1, $teacher->id);
    }
    public function test_optimistic_concurrency(): void {
        $tuple = $this->fixtures();
        $this->complete($tuple);
        [$course, $teacher, $manager, $template, $version] = $tuple;
        $this->expectException(\moodle_exception::class);
        version_manager::save($version->planid, $version->id, [], 0, $teacher->id);
    }
    public function test_diff_and_template_versions_are_distinct(): void {
        $tuple = $this->fixtures();
        $this->complete($tuple);
        [$course, $teacher, $manager, $template, $version] = $tuple;
        $newtemplate = institutional_template_manager::revise($template->templateid,
            schema::defaults(), [['reviewerid' => $manager->id, 'days' => 2]], [], $manager->id);
        $this->assertSame(2, (int)$newtemplate->revision);
        $this->assertEquals($template->id, syllabus_repository::version($version->id)->templateversionid);
        $older = syllabus_repository::version($version->id);
        $newer = clone $older;
        $payload = schema::decode($older->payload);
        $payload['syllabus'] = 'Changed';
        $newer->payload = schema::encode($payload);
        $this->assertCount(1, version_diff_engine::compare($older, $newer));
    }
    public function test_teacher_cannot_self_approve(): void {
        $tuple = $this->fixtures();
        $this->complete($tuple);
        [$course, $teacher, $manager, $template, $version] = $tuple;
        approval_service::submit($version->planid, $manager->id, $teacher->id);
        approval_service::begin($version->planid, $manager->id);
        $this->expectException(\required_capability_exception::class);
        approval_service::decide($version->planid, $teacher->id, true, '');
    }
    public function test_approvals_are_immutable_and_have_logged_actor(): void {
        global $DB;
        $tuple = $this->fixtures();
        $this->complete($tuple);
        [$course, $teacher, $manager, $template, $version] = $tuple;
        approval_service::submit($version->planid, $manager->id, $teacher->id);
        approval_service::begin($version->planid, $manager->id);
        approval_service::decide($version->planid, $manager->id, true, 'Checked');
        $frozen = syllabus_repository::version($version->id);
        $this->assertSame(hash('sha256', $frozen->payload), $frozen->contenthash);
        $approval = $DB->get_record('local_swf_approval', ['versionid' => $version->id], '*', MUST_EXIST);
        $this->assertSame((int)$manager->id, (int)$approval->reviewerid);
        $this->assertSame('Checked', $approval->comment);
        $history = $DB->get_records('local_swf_transition', ['versionid' => $version->id]);
        $this->assertCount(3, $history);
    }
    public function test_reject_wrong_version_in_locked_operation(): void {
        $tuple = $this->fixtures();
        $this->complete($tuple);
        [$course, $teacher, $manager, $template, $version] = $tuple;
        $this->expectException(\moodle_exception::class);
        approval_service::submit($version->planid, $manager->id, $teacher->id, $version->id + 900);
    }
    public function test_student_cannot_read_draft(): void {
        $tuple = $this->fixtures();
        [$course, $teacher, $manager, $template, $version] = $tuple;
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $plan = syllabus_repository::plan($version->planid);
        $this->expectException(\moodle_exception::class);
        syllabus_repository::readable_version($plan, $version->id, $student->id);
    }
    public function test_archival_revokes_current_public_visibility(): void {
        $tuple = $this->fixtures();
        $this->complete($tuple);
        [$course, $teacher, $manager, $template, $version] = $tuple;
        approval_service::submit($version->planid, $manager->id, $teacher->id);
        approval_service::begin($version->planid, $manager->id);
        approval_service::decide($version->planid, $manager->id, true, 'Checked');
        publication_service::publish($version->planid, $manager->id);
        publication_service::archive($version->planid, $manager->id);
        $this->assertEmpty(syllabus_repository::plan($version->planid)->publishedversionid);
        $this->assertSame('archived', syllabus_repository::version($version->id)->status);
    }

}
