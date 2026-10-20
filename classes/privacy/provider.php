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
 * Provider for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
/**
 * Provider implementation.
 */
final class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {
    /**
     * Get metadata.
     *
     * @param collection $collection collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_syllabusworkflow_plan', [
            'createdby' => 'privacy:createdby',
        ], 'privacy:plans');
        $collection->add_database_table('local_syllabusworkflow_version', [
            'createdby' => 'privacy:createdby', 'snapshot' => 'privacy:snapshot',
            'payload' => 'privacy:payload',
        ], 'privacy:versions');
        foreach (['local_syllabusworkflow_transition' => 'actorid', 'local_syllabusworkflow_approval' => 'reviewerid',
                'local_syllabusworkflow_assignment' => 'reviewerid', 'local_syllabusworkflow_publication' => 'actorid',
                'local_syllabusworkflow_audit' => 'actorid'] as $table => $column) {
            $collection->add_database_table($table, [$column => 'privacy:actor'], 'privacy:history');
        }
        $collection->add_external_location_link('core_message', [
            'userto' => 'privacy:recipient',
        ], 'privacy:messages');
        return $collection;
    }
    /**
     * Get contexts for userid.
     *
     * @param int $userid userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $sql = "SELECT DISTINCT ctx.id FROM {context} ctx
                  JOIN {local_syllabusworkflow_plan} p ON p.courseid = ctx.instanceid
                  LEFT JOIN {local_syllabusworkflow_version} v ON v.planid = p.id
                  LEFT JOIN {local_syllabusworkflow_assignment} a ON a.versionid = v.id
                  LEFT JOIN {local_syllabusworkflow_approval} d ON d.versionid = v.id
                  LEFT JOIN {local_syllabusworkflow_transition} t ON t.versionid = v.id
                  LEFT JOIN {local_syllabusworkflow_publication} pub ON pub.versionid = v.id
                  LEFT JOIN {local_syllabusworkflow_audit} aud ON aud.versionid = v.id
                 WHERE ctx.contextlevel = :level AND
                       (p.createdby = :owner OR v.createdby = :author OR
                       a.reviewerid = :reviewer OR d.reviewerid = :decider OR
                       t.actorid = :actor OR pub.actorid = :publisher OR aud.actorid = :auditor)";
        $list->add_from_sql($sql, [
            'level' => CONTEXT_COURSE, 'owner' => $userid, 'author' => $userid,
            'reviewer' => $userid, 'decider' => $userid, 'actor' => $userid,
            'publisher' => $userid, 'auditor' => $userid,
        ]);
        return $list;
    }
    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $plans = $DB->get_records('local_syllabusworkflow_plan', ['courseid' => $context->instanceid]);
            foreach ($plans as $plan) {
                $versions = $DB->get_records('local_syllabusworkflow_version', ['planid' => $plan->id]);
                foreach ($versions as $version) {
                    $related = (int)$version->createdby === (int)$userid ||
                        (int)$plan->createdby === (int)$userid;
                    $history = $DB->get_records('local_syllabusworkflow_transition', ['versionid' => $version->id]);
                    foreach ($history as $transition) {
                        if ((int)$transition->actorid === (int)$userid) {
                            $related = true;
                        }
                    }
                    $assignments = $DB->get_records('local_syllabusworkflow_assignment', ['versionid' => $version->id]);
                    $approvals = $DB->get_records('local_syllabusworkflow_approval', ['versionid' => $version->id]);
                    $publications = $DB->get_records('local_syllabusworkflow_publication', ['versionid' => $version->id]);
                    $audit = $DB->get_records('local_syllabusworkflow_audit', ['versionid' => $version->id]);
                    foreach (array_merge(array_values($publications), array_values($audit)) as $item) {
                        if ((int)$item->actorid === (int)$userid) {
                            $related = true;
                        }
                    }
                    foreach (array_merge(array_values($assignments), array_values($approvals)) as $item) {
                        if ((int)$item->reviewerid === (int)$userid) {
                            $related = true;
                        }
                    }
                    if (!$related) {
                        continue;
                    }
                    writer::with_context($context)->export_data([
                        get_string('pluginname', 'local_syllabusworkflow'),
                        (string)$plan->id, (string)$version->revision,
                    ], (object)[
                        'status' => $version->status, 'createdby' => $version->createdby,
                        'payload' => $version->payload, 'snapshot' => $version->snapshot,
                        'transitions' => array_values($history),
                        'assignments' => array_values($assignments),
                        'approvals' => array_values($approvals),
                        'publications' => array_values($publications),
                        'audit' => array_values($audit),
                    ]);
                }
            }
        }
    }
    /**
     * Delete data for all users in context.
     *
     * @param \context $context context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        self::delete_unpublished_drafts($context->instanceid, null);
    }
    /**
     * Delete data for user.
     *
     * @param approved_contextlist $contextlist contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_COURSE) {
                self::delete_unpublished_drafts($context->instanceid, $contextlist->get_user()->id);
            }
        }
    }
    /**
     * Delete unpublished drafts.
     *
     * @param int $courseid courseid
     * @param ?int $userid userid
     * @return void
     */
    private static function delete_unpublished_drafts(int $courseid, ?int $userid): void {
        global $DB;
        $conditions = ['courseid' => $courseid, 'publishedversionid' => null];
        if ($userid !== null) {
            $conditions['createdby'] = $userid;
        }
        // Retain approved/in-review historical records: these are institutional records.
        foreach ($DB->get_records('local_syllabusworkflow_plan', $conditions) as $plan) {
            $versions = $DB->get_records('local_syllabusworkflow_version', ['planid' => $plan->id]);
            if (count($versions) !== 1 || reset($versions)->status !== 'draft') {
                continue;
            }
            $version = reset($versions);
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('local_syllabusworkflow_audit', ['versionid' => $version->id]);
            $DB->delete_records('local_syllabusworkflow_version', ['id' => $version->id]);
            $DB->delete_records('local_syllabusworkflow_plan', ['id' => $plan->id]);
            $transaction->allow_commit();
        }
    }
}
