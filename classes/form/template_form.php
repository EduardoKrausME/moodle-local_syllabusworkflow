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
 * Template form for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\form;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');
/**
 * Template form implementation.
 */
final class template_form extends \moodleform {
    /**
     * Definition.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('templatename', 'local_syllabusworkflow'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');
        foreach (['schemajson', 'workflowjson', 'identityjson'] as $key) {
            $mform->addElement('textarea', $key, get_string($key, 'local_syllabusworkflow'),
                ['rows' => $key === 'schemajson' ? 15 : 6, 'cols' => 84]);
            $mform->setType($key, PARAM_RAW);
            $mform->addRule($key, null, 'required');
        }
        $mform->addElement('hidden', 'templateid');
        $mform->setType('templateid', PARAM_INT);
        $this->add_action_buttons();
    }
}
