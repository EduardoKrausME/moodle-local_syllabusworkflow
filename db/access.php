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
 * Access for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$capabilities = [
    'local/syllabusworkflow:view' => [
        'captype' => 'read', 'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['student' => CAP_ALLOW, 'teacher' => CAP_ALLOW, 'editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    'local/syllabusworkflow:edit' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    'local/syllabusworkflow:review' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/syllabusworkflow:approve' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/syllabusworkflow:publish' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/syllabusworkflow:manage' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/syllabusworkflow:selfapprove' => [
        'captype' => 'write', 'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [],
        'riskbitmask' => RISK_CONFIG,
    ],
];
