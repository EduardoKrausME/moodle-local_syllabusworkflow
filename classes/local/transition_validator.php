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
 * Transition validator for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Transition validator implementation.
 */
final class transition_validator {
    /**
     * Capability.
     *
     * @param string $operation operation
     * @return string
     */
    public static function capability(string $operation): string {
        $map = [
            'submit' => 'edit', 'review' => 'review', 'changes' => 'review',
            'approve' => 'approve', 'publish' => 'publish',
            'archive' => 'manage', 'revise' => 'edit',
        ];
        if (!isset($map[$operation])) {
            throw new \coding_exception('Unknown workflow operation');
        }
        return 'local/syllabusworkflow:' . $map[$operation];
    }
    /**
     * Require.
     *
     * @param \stdClass $plan plan
     * @param \stdClass $version version
     * @param int $actorid actorid
     * @param string $operation operation
     * @return void
     */
    public static function require(\stdClass $plan, \stdClass $version, int $actorid,
            string $operation): void {
        $context = syllabus_repository::course_context($plan);
        require_capability(self::capability($operation), $context, $actorid);
        if ((int)$version->planid !== (int)$plan->id ||
                (int)$plan->currentversionid !== (int)$version->id) {
            throw new \moodle_exception('staleversion', 'local_syllabusworkflow');
        }
        global $DB;
        if ($operation === 'approve' &&
                ($actorid === (int)$version->createdby || $actorid === (int)$plan->createdby ||
                $DB->record_exists('local_syllabusworkflow_audit', [
                    'versionid' => $version->id, 'actorid' => $actorid, 'action' => 'draft_saved',
                ])) && !has_capability('local/syllabusworkflow:selfapprove', $context, $actorid)) {
            throw new \moodle_exception('selfapproval', 'local_syllabusworkflow');
        }
    }
}
