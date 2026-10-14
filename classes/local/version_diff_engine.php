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
 * Version diff engine for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
defined('MOODLE_INTERNAL') || die();
final class version_diff_engine {
    public static function compare(\stdClass $previous, \stdClass $current): array {
        if ((int)$previous->planid !== (int)$current->planid) {
            throw new \moodle_exception('wrongplan', 'local_syllabusworkflow');
        }
        $left = schema::decode($previous->payload);
        $right = schema::decode($current->payload);
        $result = [];
        foreach (array_unique(array_merge(array_keys($left), array_keys($right))) as $key) {
            if (array_key_exists($key, $left) && array_key_exists($key, $right) &&
                    $left[$key] === $right[$key]) {
                continue;
            }
            $result[] = [
                'name' => in_array($key, schema::FIELDS, true) ? get_string($key, 'local_syllabusworkflow') : $key,
                'type' => !array_key_exists($key, $left) ? get_string('added', 'local_syllabusworkflow') :
                    (!array_key_exists($key, $right) ? get_string('removed', 'local_syllabusworkflow') :
                    get_string('modified', 'local_syllabusworkflow')),
                'before' => (string)($left[$key] ?? ''), 'after' => (string)($right[$key] ?? ''),
            ];
        }
        return $result;
    }
}
