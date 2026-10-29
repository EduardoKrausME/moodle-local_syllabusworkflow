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
 * Privacy API tests.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use local_syllabusworkflow\privacy\provider;

/**
 * Tests covering course and institutional template privacy operations.
 *
 * @covers \local_syllabusworkflow\privacy\provider
 */
final class privacy_provider_test extends \advanced_testcase {
    /**
     * Seed linked records without requiring workflow capabilities.
     *
     * @return array
     */
    private function records(): array {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $owner = $this->getDataGenerator()->create_user();
        $reviewer = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $now = time();
        $templateid = $DB->insert_record('local_syllabusworkflow_template', (object)[
            'name' => 'Privacy test', 'enabled' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $workflow = json_encode([['reviewerid' => $reviewer->id, 'days' => 7]]);
        $templateversionid = $DB->insert_record('local_syllabusworkflow_tplversion', (object)[
            'templateid' => $templateid, 'revision' => 1, 'schemajson' => '[]',
            'workflowjson' => $workflow, 'identityjson' => '{}',
            'contenthash' => hash('sha256', '[]' . $workflow . '{}'),
            'createdby' => $owner->id, 'timecreated' => $now,
        ]);
        $DB->set_field('local_syllabusworkflow_template', 'activeversionid',
            $templateversionid, ['id' => $templateid]);
        $planid = $DB->insert_record('local_syllabusworkflow_plan', (object)[
            'courseid' => $course->id, 'templateversionid' => $templateversionid,
            'createdby' => $owner->id, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $versionid = $DB->insert_record('local_syllabusworkflow_version', (object)[
            'planid' => $planid, 'revision' => 1, 'templateversionid' => $templateversionid,
            'payload' => '{}', 'snapshot' => '{}', 'contenthash' => hash('sha256', '{}'),
            'status' => 'approved', 'lockversion' => 0, 'createdby' => $owner->id,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->set_field('local_syllabusworkflow_plan', 'currentversionid', $versionid, ['id' => $planid]);
        $assignmentid = $DB->insert_record('local_syllabusworkflow_assignment', (object)[
            'versionid' => $versionid, 'stage' => 0, 'reviewerid' => $reviewer->id,
            'assignedby' => $other->id, 'status' => 'completed',
            'duedate' => 0, 'timecreated' => $now,
        ]);
        $DB->insert_record('local_syllabusworkflow_approval', (object)[
            'versionid' => $versionid, 'stage' => 0, 'reviewerid' => $reviewer->id,
            'decision' => 'approved', 'comment' => 'Reviewed', 'timecreated' => $now,
        ]);
        $DB->insert_record('local_syllabusworkflow_notice', (object)[
            'versionid' => $versionid, 'recipientid' => $reviewer->id, 'kind' => 'submitted',
            'dedupekey' => hash('sha256', 'privacy-test'), 'timesent' => $now,
        ]);
        return compact('course', 'owner', 'reviewer', 'other', 'templateversionid',
            'planid', 'versionid', 'assignmentid');
    }

    /**
     * Both course-scoped and site-wide references must be discoverable.
     */
    public function test_context_and_user_discovery(): void {
        $r = $this->records();
        $coursecontext = \context_course::instance($r['course']->id);
        $systemcontext = \context_system::instance();
        $ownercontexts = provider::get_contexts_for_userid($r['owner']->id)->get_contextids();
        $reviewercontexts = provider::get_contexts_for_userid($r['reviewer']->id)->get_contextids();
        $this->assertContains($coursecontext->id, $ownercontexts);
        $this->assertContains($systemcontext->id, $ownercontexts);
        $this->assertContains($coursecontext->id, $reviewercontexts);
        $this->assertContains($systemcontext->id, $reviewercontexts);
        $courseusers = new userlist($coursecontext, 'local_syllabusworkflow');
        provider::get_users_in_context($courseusers);
        $this->assertContains((int)$r['owner']->id, $courseusers->get_userids());
        $this->assertContains((int)$r['reviewer']->id, $courseusers->get_userids());
        $this->assertContains((int)$r['other']->id, $courseusers->get_userids());
        $systemusers = new userlist($systemcontext, 'local_syllabusworkflow');
        provider::get_users_in_context($systemusers);
        $this->assertContains((int)$r['owner']->id, $systemusers->get_userids());
        $this->assertContains((int)$r['reviewer']->id, $systemusers->get_userids());
        $this->assertNotContains((int)$r['other']->id, $systemusers->get_userids());
    }

    /**
     * Erasing a reviewer preserves the approval but removes direct references.
     */
    public function test_delete_user_preserves_other_people_and_decisions(): void {
        global $DB;
        $r = $this->records();
        $contexts = [\context_course::instance($r['course']->id)->id, \context_system::instance()->id];
        provider::delete_data_for_user(new approved_contextlist(
            $r['reviewer'], 'local_syllabusworkflow', $contexts));
        $assignment = $DB->get_record('local_syllabusworkflow_assignment', ['id' => $r['assignmentid']]);
        $this->assertSame(-$r['assignmentid'], (int)$assignment->reviewerid);
        $this->assertSame((int)$r['other']->id, (int)$assignment->assignedby);
        $approval = $DB->get_record('local_syllabusworkflow_approval', ['versionid' => $r['versionid']]);
        $this->assertSame(0, (int)$approval->reviewerid);
        $this->assertSame('approved', $approval->decision);
        $this->assertFalse($DB->record_exists('local_syllabusworkflow_notice',
            ['versionid' => $r['versionid'], 'recipientid' => $r['reviewer']->id]));
        $template = $DB->get_record('local_syllabusworkflow_tplversion', ['id' => $r['templateversionid']]);
        $this->assertSame(0, (int)json_decode($template->workflowjson, true)[0]['reviewerid']);
        $this->assertSame((int)$r['owner']->id, (int)$template->createdby);
        $this->assertSame(hash('sha256',
            $template->schemajson . $template->workflowjson . $template->identityjson),
            $template->contenthash);
    }

    /**
     * Multi-user deletion is limited to the authorized course context.
     */
    public function test_delete_users_maintains_unrelated_template_creators(): void {
        global $DB;
        $r = $this->records();
        $coursecontext = \context_course::instance($r['course']->id);
        provider::delete_data_for_users(new approved_userlist($coursecontext,
            'local_syllabusworkflow', [$r['owner']->id, $r['other']->id]));
        $this->assertSame(0, (int)$DB->get_field('local_syllabusworkflow_plan',
            'createdby', ['id' => $r['planid']]));
        $this->assertSame(0, (int)$DB->get_field('local_syllabusworkflow_assignment',
            'assignedby', ['id' => $r['assignmentid']]));
        $this->assertSame((int)$r['reviewer']->id, (int)$DB->get_field(
            'local_syllabusworkflow_assignment', 'reviewerid', ['id' => $r['assignmentid']]));
        $this->assertSame((int)$r['owner']->id, (int)$DB->get_field(
            'local_syllabusworkflow_tplversion', 'createdby', ['id' => $r['templateversionid']]));
    }

    /**
     * Deleting course context data must not delete the institutional audit history.
     */
    public function test_delete_all_users_in_course_preserves_approval(): void {
        global $DB;
        $r = $this->records();
        provider::delete_data_for_all_users_in_context(\context_course::instance($r['course']->id));
        $this->assertSame(0, (int)$DB->get_field('local_syllabusworkflow_approval',
            'reviewerid', ['versionid' => $r['versionid']]));
        $this->assertSame('approved', $DB->get_field('local_syllabusworkflow_approval',
            'decision', ['versionid' => $r['versionid']]));
        $this->assertSame(-$r['assignmentid'], (int)$DB->get_field(
            'local_syllabusworkflow_assignment', 'reviewerid', ['id' => $r['assignmentid']]));
    }
}
