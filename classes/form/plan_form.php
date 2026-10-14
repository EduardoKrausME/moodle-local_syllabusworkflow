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
 * Plan form for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\form;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');
final class plan_form extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $fields = $this->_customdata['fields'];
        foreach ($fields as $field) {
            $key = $field['key'];
            $name = 'f_' . $key;
            $mform->addElement('textarea', $name, get_string($key, 'local_syllabusworkflow'),
                ['rows' => 4, 'cols' => 76]);
            $mform->setType($name, PARAM_RAW_TRIMMED);
            if (!empty($field['guidance'])) {
                $mform->addElement('static', $name . '_guidance', '', s($field['guidance']));
            }
            if (!empty($field['locked'])) {
                $mform->freeze($name);
            }
        }
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'versionid');
        $mform->setType('versionid', PARAM_INT);
        $mform->addElement('hidden', 'lockversion');
        $mform->setType('lockversion', PARAM_INT);
        $this->add_action_buttons(true, get_string('savedraft', 'local_syllabusworkflow'));
    }
}
