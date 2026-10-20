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
 * Syllabus changed for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\event;
/**
 * Syllabus changed implementation.
 */
final class syllabus_changed extends \core\event\base {
    /**
     * Init.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'local_syllabusworkflow_version';
    }
    /**
     * Get name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventsyllabuschanged', 'local_syllabusworkflow');
    }
    /**
     * Get description.
     *
     * @return string
     */
    public function get_description(): string {
        return 'User ' . $this->userid . ' changed syllabus version ' . $this->objectid .
            ' using action ' . $this->other['action'] . '.';
    }
    /**
     * Get url.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/syllabusworkflow/view.php', ['id' => $this->other['planid']]);
    }
}
