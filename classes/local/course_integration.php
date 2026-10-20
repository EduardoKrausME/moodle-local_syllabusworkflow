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
 * Course integration for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Course integration implementation.
 */
final class course_integration {
    /**
     * Link.
     *
     * @param int $planid planid
     * @return \moodle_url
     */
    public static function link(int $planid): \moodle_url {
        return new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $planid]);
    }
    /**
     * Plans.
     *
     * @param int $courseid courseid
     * @return array
     */
    public static function plans(int $courseid): array {
        global $DB;
        return $DB->get_records('local_syllabusworkflow_plan', ['courseid' => $courseid], 'id ASC');
    }
}
