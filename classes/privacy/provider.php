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
 * Privacy provider for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation for syllabus plans and site-wide institutional templates.
 */
final class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {
    /**
     * User ID columns in course-scoped data. All references are anonymised, not only the creator.
     *
     * @return array
     */
    private static function course_user_columns(): array {
        return [
            'local_syllabusworkflow_plan' => ['createdby'],
            'local_syllabusworkflow_version' => ['createdby'],
            'local_syllabusworkflow_transition' => ['actorid'],
            'local_syllabusworkflow_assignment' => ['reviewerid', 'assignedby'],
            'local_syllabusworkflow_approval' => ['reviewerid'],
            'local_syllabusworkflow_publication' => ['actorid'],
            'local_syllabusworkflow_audit' => ['actorid'],
            'local_syllabusworkflow_notice' => ['recipientid'],
        ];
    }

    /**
     * Return the FROM/JOIN clauses for a course-scoped table.
     *
     * @param string $table table name from course_user_columns()
     * @return string
     */
    private static function course_from(string $table): string {
        if ($table === 'local_syllabusworkflow_plan') {
            return '{local_syllabusworkflow_plan} p';
        }
        if ($table === 'local_syllabusworkflow_version') {
            return '{local_syllabusworkflow_version} t
                    JOIN {local_syllabusworkflow_plan} p ON p.id = t.planid';
        }
        if ($table === 'local_syllabusworkflow_publication') {
            return '{local_syllabusworkflow_publication} t
                    JOIN {local_syllabusworkflow_plan} p ON p.id = t.planid';
        }
        return '{' . $table . '} t
                JOIN {local_syllabusworkflow_version} v ON v.id = t.versionid
                JOIN {local_syllabusworkflow_plan} p ON p.id = v.planid';
    }

    /**
     * Get privacy metadata.
     *
     * @param collection $collection metadata collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_syllabusworkflow_plan', [
            'createdby' => 'privacy:createdby',
        ], 'privacy:plans');
        $collection->add_database_table('local_syllabusworkflow_version', [
            'createdby' => 'privacy:createdby',
            'snapshot' => 'privacy:snapshot',
            'payload' => 'privacy:payload',
        ], 'privacy:versions');
        $collection->add_database_table('local_syllabusworkflow_tplversion', [
            'createdby' => 'privacy:createdby',
            'workflowjson' => 'privacy:workflowjson',
            'identityjson' => 'privacy:identityjson',
        ], 'privacy:templates');
        foreach ([
            'local_syllabusworkflow_transition' => ['actorid' => 'privacy:actor', 'comment' => 'privacy:comment'],
            'local_syllabusworkflow_approval' => ['reviewerid' => 'privacy:actor', 'comment' => 'privacy:comment'],
            'local_syllabusworkflow_assignment' => [
                'reviewerid' => 'privacy:actor', 'assignedby' => 'privacy:actor',
            ],
            'local_syllabusworkflow_publication' => ['actorid' => 'privacy:actor'],
            'local_syllabusworkflow_audit' => ['actorid' => 'privacy:actor', 'comment' => 'privacy:comment'],
            'local_syllabusworkflow_notice' => ['recipientid' => 'privacy:recipient'],
        ] as $table => $fields) {
            $collection->add_database_table($table, $fields, 'privacy:history');
        }
        $collection->add_external_location_link('core_message', [
            'userto' => 'privacy:recipient',
        ], 'privacy:messages');
        return $collection;
    }

    /**
     * Whether the given user appears in any template's configured review stages.
     *
     * @param int $userid user ID
     * @return bool
     */
    private static function is_template_reviewer(int $userid): bool {
        global $DB;
        $records = $DB->get_recordset('local_syllabusworkflow_tplversion', null, '', 'id, workflowjson');
        foreach ($records as $record) {
            $stages = json_decode($record->workflowjson, true);
            foreach (is_array($stages) ? $stages : [] as $stage) {
                if ((int)($stage['reviewerid'] ?? 0) === $userid) {
                    $records->close();
                    return true;
                }
            }
        }
        $records->close();
        return false;
    }

    /**
     * Find course contexts for any direct user reference and the system context for templates.
     *
     * @param int $userid user ID
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $list = new contextlist();
        foreach (self::course_user_columns() as $table => $columns) {
            $alias = $table === 'local_syllabusworkflow_plan' ? 'p' : 't';
            foreach ($columns as $column) {
                $from = self::course_from($table);
                $sql = "SELECT DISTINCT ctx.id FROM {$from}
                          JOIN {context} ctx ON ctx.instanceid = p.courseid
                         WHERE ctx.contextlevel = :contextlevel AND {$alias}.{$column} = :userid";
                $list->add_from_sql($sql, ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]);
            }
        }
        if ($DB->record_exists('local_syllabusworkflow_tplversion', ['createdby' => $userid]) ||
                self::is_template_reviewer($userid)) {
            $list->add_system_context();
        }
        return $list;
    }

    /**
     * List every user referenced in the supplied course or system context.
     *
     * @param userlist $userlist user list
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $userlist->add_from_sql('userid',
                'SELECT DISTINCT createdby AS userid FROM {local_syllabusworkflow_tplversion} WHERE createdby > 0', []);
            $records = $DB->get_recordset('local_syllabusworkflow_tplversion', null, '', 'id, workflowjson');
            $reviewers = [];
            foreach ($records as $record) {
                $stages = json_decode($record->workflowjson, true);
                foreach (is_array($stages) ? $stages : [] as $stage) {
                    $id = (int)($stage['reviewerid'] ?? 0);
                    if ($id > 0) {
                        $reviewers[$id] = $id;
                    }
                }
            }
            $records->close();
            $userlist->add_users(array_values($reviewers));
            return;
        }
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        foreach (self::course_user_columns() as $table => $columns) {
            $alias = $table === 'local_syllabusworkflow_plan' ? 'p' : 't';
            foreach ($columns as $column) {
                $sql = "SELECT DISTINCT {$alias}.{$column} AS userid
                          FROM " . self::course_from($table) . "
                         WHERE p.courseid = :courseid AND {$alias}.{$column} > 0";
                $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
            }
        }
    }

    /**
     * Export a user's template revisions and configured review stages.
     *
     * @param \context $context system context
     * @param int $userid user ID
     * @return void
     */
    private static function export_templates(\context $context, int $userid): void {
        global $DB;
        foreach ($DB->get_records('local_syllabusworkflow_tplversion') as $version) {
            $stages = json_decode($version->workflowjson, true);
            $userstages = [];
            foreach (is_array($stages) ? $stages : [] as $index => $stage) {
                if ((int)($stage['reviewerid'] ?? 0) === $userid) {
                    $userstages[] = ['stage' => $index, 'reviewerid' => $userid];
                }
            }
            if ((int)$version->createdby !== $userid && !$userstages) {
                continue;
            }
            $data = (object)[
                'templateid' => $version->templateid,
                'revision' => $version->revision,
                'createdby' => (int)$version->createdby === $userid ? $userid : null,
                'reviewstages' => $userstages,
                'timecreated' => $version->timecreated,
            ];
            writer::with_context($context)->export_data([
                get_string('pluginname', 'local_syllabusworkflow'), 'templates',
                (string)$version->templateid, (string)$version->revision,
            ], $data);
        }
    }

    /**
     * Export only the records directly associated with this user.
     *
     * @param \context $context course context
     * @param int $userid user ID
     * @return void
     */
    private static function export_course(\context $context, int $userid): void {
        global $DB;
        $plans = $DB->get_records('local_syllabusworkflow_plan', ['courseid' => $context->instanceid]);
        foreach ($plans as $plan) {
            $isowner = (int)$plan->createdby === $userid;
            foreach ($DB->get_records('local_syllabusworkflow_version', ['planid' => $plan->id]) as $version) {
                $isauthor = (int)$version->createdby === $userid;
                $data = (object)[];
                if ($isowner || $isauthor) {
                    $data->version = (object)[
                        'createdby' => $version->createdby,
                        'status' => $version->status,
                        'payload' => $version->payload,
                        'snapshot' => $version->snapshot,
                    ];
                }
                foreach (self::course_user_columns() as $table => $columns) {
                    if ($table === 'local_syllabusworkflow_plan' ||
                            $table === 'local_syllabusworkflow_version') {
                        continue;
                    }
                    $related = [];
                    $conditions = $table === 'local_syllabusworkflow_publication'
                        ? ['planid' => $plan->id, 'versionid' => $version->id]
                        : ['versionid' => $version->id];
                    foreach ($DB->get_records($table, $conditions) as $record) {
                        foreach ($columns as $column) {
                            if ((int)$record->$column === $userid) {
                                $related[] = $record;
                                break;
                            }
                        }
                    }
                    if ($related) {
                        $data->$table = array_values($related);
                    }
                }
                if (!$isowner && !$isauthor && !$data) {
                    continue;
                }
                writer::with_context($context)->export_data([
                    get_string('pluginname', 'local_syllabusworkflow'),
                    (string)$plan->id, (string)$version->revision,
                ], $data);
            }
        }
    }

    /**
     * Export a user's data from approved contexts.
     *
     * @param approved_contextlist $contextlist approved contexts
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                self::export_templates($context, $userid);
            } else if ($context->contextlevel === CONTEXT_COURSE) {
                self::export_course($context, $userid);
            }
        }
    }

    /**
     * Anonymise a user's references in a single course.
     *
     * @param int $courseid course ID
     * @param ?int $userid user ID, or null for all users
     * @return void
     */
    private static function anonymise_course(int $courseid, ?int $userid): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        foreach ($DB->get_records('local_syllabusworkflow_plan', ['courseid' => $courseid]) as $plan) {
            foreach ($DB->get_records('local_syllabusworkflow_version', ['planid' => $plan->id]) as $version) {
                foreach (self::course_user_columns() as $table => $columns) {
                    if ($table === 'local_syllabusworkflow_plan' ||
                            $table === 'local_syllabusworkflow_version') {
                        continue;
                    }
                    $conditions = $table === 'local_syllabusworkflow_publication'
                        ? ['planid' => $plan->id, 'versionid' => $version->id]
                        : ['versionid' => $version->id];
                    if ($table === 'local_syllabusworkflow_notice') {
                        if ($userid !== null) {
                            $conditions['recipientid'] = $userid;
                        }
                        // No need to retain notification delivery ledgers after erasure.
                        $DB->delete_records($table, $conditions);
                        continue;
                    }
                    foreach ($columns as $column) {
                        $where = ($table === 'local_syllabusworkflow_publication')
                            ? 'planid = :planid AND versionid = :versionid'
                            : 'versionid = :versionid';
                        $params = $conditions;
                        if ($userid !== null) {
                            $where .= ' AND ' . $column . ' = :userid';
                            $params['userid'] = $userid;
                        }
                        $DB->set_field_select($table, $column, 0, $where, $params);
                    }
                }
                if ($userid === null || (int)$version->createdby === $userid) {
                    $DB->set_field('local_syllabusworkflow_version', 'createdby', 0, ['id' => $version->id]);
                }
            }
            if ($userid === null || (int)$plan->createdby === $userid) {
                $DB->set_field('local_syllabusworkflow_plan', 'createdby', 0, ['id' => $plan->id]);
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Anonymise creator references and configured reviewer IDs in institutional templates.
     *
     * @param ?int $userid user ID, or null for all users
     * @return void
     */
    private static function anonymise_templates(?int $userid): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        foreach ($DB->get_records('local_syllabusworkflow_tplversion') as $version) {
            $updates = ['id' => $version->id];
            if ($userid === null || (int)$version->createdby === $userid) {
                $updates['createdby'] = 0;
            }
            $stages = json_decode($version->workflowjson, true);
            if (is_array($stages)) {
                $changed = false;
                foreach ($stages as &$stage) {
                    if (isset($stage['reviewerid']) && ($userid === null ||
                            (int)$stage['reviewerid'] === $userid) && (int)$stage['reviewerid'] > 0) {
                        // The runtime falls back to the current approver when no reviewer is configured.
                        $stage['reviewerid'] = 0;
                        $changed = true;
                    }
                }
                unset($stage);
                if ($changed) {
                    $updates['workflowjson'] = json_encode($stages,
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $updates['contenthash'] = hash('sha256',
                        $version->schemajson . $updates['workflowjson'] . $version->identityjson);
                }
            }
            if (count($updates) > 1) {
                $DB->update_record('local_syllabusworkflow_tplversion', (object)$updates);
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Retain documents and decisions while removing IDs and notification records.
     *
     * @param \context $context context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            self::anonymise_templates(null);
        } else if ($context->contextlevel === CONTEXT_COURSE) {
            self::anonymise_course($context->instanceid, null);
        }
    }

    /**
     * Erase user references only in the approved contexts.
     *
     * @param approved_contextlist $contextlist approved contexts
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                self::anonymise_templates($userid);
            } else if ($context->contextlevel === CONTEXT_COURSE) {
                self::anonymise_course($context->instanceid, $userid);
            }
        }
    }

    /**
     * Erase several users only in the approved context.
     *
     * @param approved_userlist $userlist approved users
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        foreach ($userlist->get_userids() as $userid) {
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                self::anonymise_templates((int)$userid);
            } else if ($context->contextlevel === CONTEXT_COURSE) {
                self::anonymise_course($context->instanceid, (int)$userid);
            }
        }
    }
}
