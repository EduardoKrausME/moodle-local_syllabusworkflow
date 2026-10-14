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
 * Lib for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_syllabusworkflow_extend_navigation_course(\navigation_node $navigation,
        \stdClass $course, \context_course $context): void {
    if (!has_capability('local/syllabusworkflow:view', $context) &&
            !has_capability('local/syllabusworkflow:edit', $context)) {
        return;
    }
    $navigation->add(get_string('pluginname', 'local_syllabusworkflow'),
        new \moodle_url('/local/syllabusworkflow/index.php', ['courseid' => $course->id]),
        \navigation_node::TYPE_CUSTOM);
}
