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
 * Notification service for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Notification service implementation.
 */
final class notification_service {
    /**
     * Send.
     *
     * @param int $versionid versionid
     * @param int $recipientid recipientid
     * @param string $kind kind
     * @param string $key key
     * @return bool
     */
    public static function send(int $versionid, int $recipientid, string $kind, string $key): bool {
        global $DB;
        if ($recipientid <= 0 || !in_array($kind,
                ['submitted', 'changesrequested', 'approved', 'published', 'duedate'], true)) {
            return false;
        }
        $digest = hash('sha256', $versionid . ':' . $recipientid . ':' . $kind . ':' . $key);
        $factory = \core\lock\lock_config::get_lock_factory('local_syllabusworkflow');
        $lock = $factory->get_lock('message_' . $digest, 5);
        if (!$lock) {
            throw new \moodle_exception('lockfailed', 'local_syllabusworkflow');
        }
        try {
            if ($DB->record_exists('local_syllabusworkflow_notice', ['dedupekey' => $digest])) {
                return false;
            }
            $version = syllabus_repository::version($versionid);
            $plan = syllabus_repository::plan($version->planid);
            $context = syllabus_repository::course_context($plan);
            if (!has_capability('local/syllabusworkflow:edit', $context, $recipientid) &&
                    !has_capability('local/syllabusworkflow:review', $context, $recipientid) &&
                    !has_capability('local/syllabusworkflow:approve', $context, $recipientid) &&
                    !has_capability('local/syllabusworkflow:publish', $context, $recipientid)) {
                return false;
            }
            $message = new \core\message\message();
            $message->component = 'local_syllabusworkflow';
            $message->name = $kind;
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $DB->get_record('user', ['id' => $recipientid], '*', MUST_EXIST);
            $message->subject = get_string('message' . $kind, 'local_syllabusworkflow');
            $message->fullmessage = get_string('messagebody', 'local_syllabusworkflow');
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = '';
            $message->smallmessage = $message->subject;
            $message->notification = 1;
            $message->contexturl = (new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $plan->id]))->out(false);
            $message->contexturlname = get_string('pluginname', 'local_syllabusworkflow');
            // Reserve key before sending to avoid duplicates across worker retries.
            $id = $DB->insert_record('local_syllabusworkflow_notice', (object)[
                'versionid' => $versionid, 'recipientid' => $recipientid, 'kind' => $kind,
                'dedupekey' => $digest, 'timesent' => time(),
            ]);
            try {
                if (!message_send($message)) {
                    throw new \moodle_exception('sendfailed', 'local_syllabusworkflow');
                }
            } catch (\Throwable $e) {
                $DB->delete_records('local_syllabusworkflow_notice', ['id' => $id]);
                throw $e;
            }
            return true;
        } finally {
            $lock->release();
        }
    }
}
