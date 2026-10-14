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
 * Workflow state machine for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
defined('MOODLE_INTERNAL') || die();
final class workflow_state_machine {
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const UNDER_REVIEW = 'under_review';
    public const CHANGES_REQUESTED = 'changes_requested';
    public const APPROVED = 'approved';
    public const PUBLISHED = 'published';
    public const ARCHIVED = 'archived';
    public const TRANSITIONS = [
        self::DRAFT => [self::SUBMITTED, self::ARCHIVED],
        self::SUBMITTED => [self::UNDER_REVIEW, self::CHANGES_REQUESTED],
        self::UNDER_REVIEW => [self::CHANGES_REQUESTED, self::APPROVED],
        self::CHANGES_REQUESTED => [self::ARCHIVED],
        self::APPROVED => [self::PUBLISHED, self::ARCHIVED],
        self::PUBLISHED => [self::ARCHIVED],
        self::ARCHIVED => [],
    ];
    public static function assert(string $from, string $to): void {
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new \moodle_exception('invalidtransition', 'local_syllabusworkflow');
        }
    }
}
