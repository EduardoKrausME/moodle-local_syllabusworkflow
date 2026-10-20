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
 * Schema for institutional syllabus management.
 *
 * @package    local_syllabusworkflow
 * @copyright  2026 Eduardo Kraus <https://eduardokraus.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_syllabusworkflow\local;
/**
 * Schema implementation.
 */
final class schema {
    /** Fields. */
    public const FIELDS = [
        'courseidentification', 'disciplinename', 'disciplinecode', 'term', 'workload',
        'teacher', 'syllabus', 'generalobjectives', 'specificobjectives', 'competencies',
        'contents', 'methodology', 'resources', 'assessmentcriteria', 'assessmenttools',
        'schedule', 'bibliography', 'furtherbibliography', 'institutionalnotes',
    ];
    /**
     * Defaults.
     *
     * @return array
     */
    public static function defaults(): array {
        $fields = [];
        foreach (self::FIELDS as $name) {
            $fields[] = ['key' => $name, 'required' => in_array($name,
                ['disciplinename', 'syllabus', 'generalobjectives', 'assessmentcriteria'], true),
                'locked' => false, 'guidance' => ''];
        }
        return $fields;
    }
    /**
     * Validate definition.
     *
     * @param array $fields fields
     * @return void
     */
    public static function validate_definition(array $fields): void {
        $seen = [];
        foreach ($fields as $field) {
            if (!is_array($field) || !isset($field['key']) || !in_array($field['key'], self::FIELDS, true) ||
                    isset($seen[$field['key']])) {
                throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
            }
            $seen[$field['key']] = true;
            if (isset($field['guidance']) && !is_string($field['guidance'])) {
                throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
            }
            if (isset($field['default']) && !is_string($field['default'])) {
                throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
            }
            if ($field['locked'] && $field['required'] && empty($field['default'])) {
                throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
            }
            if (isset($field['maxlen']) && (!is_int($field['maxlen']) ||
                    $field['maxlen'] < 1 || $field['maxlen'] > 20000)) {
                throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
            }
            if (!array_key_exists('required', $field) || !array_key_exists('locked', $field) ||
                    !is_bool($field['required']) || !is_bool($field['locked'])) {
                throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
            }
        }
        if (!$seen) {
            throw new \moodle_exception('invalidschema', 'local_syllabusworkflow');
        }
    }
    /**
     * Validate payload.
     *
     * @param array $data data
     * @param array $fields fields
     * @param ?array $previous previous
     * @return array
     */
    public static function validate_payload(array $data, array $fields, ?array $previous = null): array {
        self::validate_definition($fields);
        $clean = [];
        foreach ($fields as $field) {
            $key = $field['key'];
            $value = $data[$key] ?? '';
            if (!is_string($value) || \core_text::strlen($value) > 20000) {
                throw new \moodle_exception('invalidfield', 'local_syllabusworkflow', '',
                    get_string($key, 'local_syllabusworkflow'));
            }
            $value = trim($value);
            if (isset($field['maxlen']) && (int)$field['maxlen'] > 0 &&
                    \core_text::strlen($value) > (int)$field['maxlen']) {
                throw new \moodle_exception('invalidfield', 'local_syllabusworkflow', '',
                    get_string($key, 'local_syllabusworkflow'));
            }
            if ($field['required'] && $value === '') {
                throw new \moodle_exception('requiredfield', 'local_syllabusworkflow', '',
                    get_string($key, 'local_syllabusworkflow'));
            }
            if ($field['locked'] && $previous !== null && ($previous[$key] ?? '') !== $value) {
                throw new \moodle_exception('lockedfield', 'local_syllabusworkflow', '',
                    get_string($key, 'local_syllabusworkflow'));
            }
            $clean[$key] = $value;
        }
        return $clean;
    }
    /**
     * Decode.
     *
     * @param string $json json
     * @return array
     */
    public static function decode(string $json): array {
        $value = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($value)) {
            throw new \coding_exception('Expected JSON object');
        }
        return $value;
    }
    /**
     * Encode.
     *
     * @param array $value value
     * @return string
     */
    public static function encode(array $value): string {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
