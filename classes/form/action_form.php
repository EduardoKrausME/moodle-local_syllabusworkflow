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
 * Action form for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\form;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');
final class action_form extends \moodleform {
    protected function definition(): void {
        $form = $this->_form;
        $action = $this->_customdata['action'];
        if (in_array($action, ['submit', 'delegate'], true)) {
            $form->addElement('select', 'reviewerid', get_string('reviewerid', 'local_syllabusworkflow'),
                $this->_customdata['reviewers']);
            $form->addRule('reviewerid', null, 'required');
        }
        if (in_array($action, ['approve', 'changes'], true)) {
            $form->addElement('textarea', 'comment', get_string('comment', 'local_syllabusworkflow'),
                ['rows' => 5, 'cols' => 80, 'maxlength' => 2000]);
            $form->setType('comment', PARAM_TEXT);
            if ($action === 'changes') {
                $form->addRule('comment', null, 'required');
            }
        }
        $form->addElement('hidden', 'id', $this->_customdata['id']);
        $form->setType('id', PARAM_INT);
        $form->addElement('hidden', 'action', $action);
        $form->setType('action', PARAM_ALPHA);
        $form->addElement('hidden', 'versionid', $this->_customdata['versionid']);
        $form->setType('versionid', PARAM_INT);
        $this->add_action_buttons(true, get_string($action === 'changes' ? 'changes' :
            ($action === 'revise' ? 'newrevision' : $action), 'local_syllabusworkflow'));
    }
}
