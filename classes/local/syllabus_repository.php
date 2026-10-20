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
 * Syllabus repository for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Syllabus repository implementation.
 */
final class syllabus_repository {
    /**
     * Plan.
     *
     * @param int $id id
     * @return \stdClass
     */
    public static function plan(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_syllabusworkflow_plan', ['id' => $id], '*', MUST_EXIST);
    }
    /**
     * Version.
     *
     * @param int $id id
     * @return \stdClass
     */
    public static function version(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_syllabusworkflow_version', ['id' => $id], '*', MUST_EXIST);
    }
    /**
     * Template.
     *
     * @param int $id id
     * @return \stdClass
     */
    public static function template(int $id): \stdClass {
        global $DB;
        return $DB->get_record('local_syllabusworkflow_tplversion', ['id' => $id], '*', MUST_EXIST);
    }
    /**
     * Course context.
     *
     * @param \stdClass $plan plan
     * @return \context_course
     */
    public static function course_context(\stdClass $plan): \context_course {
        return \context_course::instance($plan->courseid);
    }
    /**
     * Readable version.
     *
     * @param \stdClass $plan plan
     * @param int $requested requested
     * @param int $userid userid
     * @return \stdClass
     */
    public static function readable_version(\stdClass $plan, int $requested, int $userid): \stdClass {
        $context = self::course_context($plan);
        if (!has_capability('local/syllabusworkflow:view', $context, $userid) &&
                !has_capability('local/syllabusworkflow:edit', $context, $userid) &&
                !has_capability('local/syllabusworkflow:review', $context, $userid)) {
            throw new \required_capability_exception($context, 'local/syllabusworkflow:view', 'nopermissions', '');
        }
        $privileged = has_capability('local/syllabusworkflow:edit', $context, $userid) ||
            has_capability('local/syllabusworkflow:review', $context, $userid) ||
            has_capability('local/syllabusworkflow:approve', $context, $userid) ||
            has_capability('local/syllabusworkflow:publish', $context, $userid);
        if (!$privileged && (!$plan->publishedversionid || $requested !== (int)$plan->publishedversionid)) {
            throw new \moodle_exception('notpublished', 'local_syllabusworkflow');
        }
        $version = self::version($requested);
        if ((int)$version->planid !== (int)$plan->id) {
            throw new \moodle_exception('wrongplan', 'local_syllabusworkflow');
        }
        return $version;
    }
}
