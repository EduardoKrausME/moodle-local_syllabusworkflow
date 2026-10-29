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
 * Local syllabusworkflow for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['actionsaved'] = 'Workflow action completed';
$string['added'] = 'Added';
$string['after'] = 'After';
$string['approve'] = 'Approve';
$string['approvedplans'] = 'Approved plans';
$string['archive'] = 'Archive syllabus';
$string['assessmentcriteria'] = 'Assessment criteria';
$string['assessmenttools'] = 'Assessment instruments';
$string['author'] = 'Author';
$string['before'] = 'Before';
$string['bibliography'] = 'Basic bibliography';
$string['changes'] = 'Request changes';
$string['comment'] = 'Comment or justification';
$string['compare'] = 'Compare revisions';
$string['competencies'] = 'Competencies and skills';
$string['contents'] = 'Syllabus content';
$string['copylink'] = 'Copy course link';
$string['courseidentification'] = 'Course identification';
$string['createplan'] = 'Create syllabus';
$string['defaulttemplate'] = 'Standard institutional syllabus';
$string['delegate'] = 'Delegate review';
$string['disciplinecode'] = 'Discipline code';
$string['disciplinename'] = 'Discipline name';
$string['downloadhtml'] = 'Download HTML';
$string['editplan'] = 'Edit syllabus';
$string['eventsyllabuschanged'] = 'Syllabus workflow state changed';
$string['export'] = 'Printable HTML';
$string['furtherbibliography'] = 'Additional bibliography';
$string['generalobjectives'] = 'General objectives';
$string['hash'] = 'Content SHA-256';
$string['history'] = 'Version history';
$string['identityjson'] = 'Institution identity (JSON)';
$string['institutionalnotes'] = 'Institutional notes';
$string['invalidaction'] = 'Unknown workflow operation';
$string['invaliddecision'] = 'Invalid decision or missing justification';
$string['invalidfield'] = 'Invalid field: {$a}';
$string['invalidreviewer'] = 'The selected reviewer needs permission to approve syllabi';
$string['invalidrevision'] = 'A new revision is not permitted in this state';
$string['invalidschema'] = 'Invalid institutional field schema';
$string['invalidstages'] = 'Invalid institutional approval stages';
$string['invalidtransition'] = 'Invalid state transition';
$string['lockedfield'] = 'Field cannot be changed: {$a}';
$string['lockfailed'] = 'Another operation has locked this plan';
$string['messageapproved'] = 'Syllabus approved';
$string['messagebody'] = 'A syllabus requires your attention. Open Moodle to view details.';
$string['messagechangesrequested'] = 'Changes requested for a syllabus';
$string['messageduedate'] = 'Syllabus review deadline reminder';
$string['messageprovider:approved'] = 'Syllabus approved';
$string['messageprovider:changesrequested'] = 'Syllabus corrections requested';
$string['messageprovider:duedate'] = 'Syllabus review deadline';
$string['messageprovider:published'] = 'Syllabus published';
$string['messageprovider:submitted'] = 'Syllabus submitted for review';
$string['messagepublished'] = 'Syllabus published';
$string['messagesubmitted'] = 'Syllabus submitted for review';
$string['methodology'] = 'Teaching methodology';
$string['models'] = 'Institutional templates';
$string['modified'] = 'Modified';
$string['newrevision'] = 'Create new revision';
$string['noplan'] = 'No syllabus exists for this course';
$string['notpublished'] = 'No readable published syllabus is available';
$string['pending'] = 'Pending syllabi';
$string['period'] = 'Term';
$string['pluginname'] = 'Syllabus workflow';
$string['privacy:actor'] = 'User responsible for the operation';
$string['privacy:comment'] = 'Comment or justification recorded by a user';
$string['privacy:identityjson'] = 'Institutional identity associated with a template';
$string['privacy:templates'] = 'Institutional template revisions';
$string['privacy:workflowjson'] = 'Configured reviewers in institutional approval stages';
$string['privacy:createdby'] = 'Plan or revision creator';
$string['privacy:history'] = 'Institutional audit history';
$string['privacy:messages'] = 'Message notifications';
$string['privacy:payload'] = 'Syllabus document body';
$string['privacy:plans'] = 'Syllabus metadata';
$string['privacy:recipient'] = 'Message recipient';
$string['privacy:snapshot'] = 'Frozen course/author identity';
$string['privacy:versions'] = 'Syllabus versions';
$string['publish'] = 'Publish';
$string['readonlyversion'] = 'Only draft versions can be edited';
$string['removed'] = 'Removed';
$string['reports'] = 'Reports';
$string['requiredfield'] = 'Required field is empty: {$a}';
$string['resources'] = 'Teaching resources';
$string['review'] = 'Begin review';
$string['reviewerid'] = 'Reviewer user ID';
$string['revision'] = 'Revision';
$string['saved'] = 'Draft saved';
$string['savedraft'] = 'Save draft';
$string['schedule'] = 'Activity schedule';
$string['schemajson'] = 'Fields and rules (JSON)';
$string['selfapproval'] = 'You cannot approve your own syllabus';
$string['sendfailed'] = 'Message sending failed';
$string['specificobjectives'] = 'Specific objectives';
$string['staleversion'] = 'This revision was modified by another user or is no longer current';
$string['state_approved'] = 'Approved';
$string['state_archived'] = 'Archived';
$string['state_changes_requested'] = 'Changes requested';
$string['state_draft'] = 'Draft';
$string['state_published'] = 'Published';
$string['state_submitted'] = 'Submitted';
$string['state_under_review'] = 'Under review';
$string['status'] = 'Status';
$string['submit'] = 'Submit for review';
$string['syllabus'] = 'Syllabus';
$string['syllabusworkflow:approve'] = 'Approve syllabi';
$string['syllabusworkflow:edit'] = 'Create and edit syllabi';
$string['syllabusworkflow:manage'] = 'Manage syllabus workflow';
$string['syllabusworkflow:publish'] = 'Publish approved syllabi';
$string['syllabusworkflow:review'] = 'Review submitted syllabi';
$string['syllabusworkflow:selfapprove'] = 'Approve own syllabi when permitted';
$string['syllabusworkflow:view'] = 'View published syllabi';
$string['taskduedates'] = 'Notify reviewers of pending deadlines';
$string['teacher'] = 'Responsible teacher';
$string['templatename'] = 'Template name';
$string['term'] = 'Academic term';
$string['transitions'] = 'Decision history';
$string['viewplan'] = 'View syllabus';
$string['workflowjson'] = 'Approval stages (JSON)';
$string['workload'] = 'Course workload';
$string['wrongplan'] = 'This revision does not belong to the requested plan';
