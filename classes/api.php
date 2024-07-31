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

namespace mod_rplace;
use html_writer;

/**
 * Class api
 *
 * @package    mod_rplace
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    const COLORS = [
        '#ffffff',
        '#be0039',
        '#ff4500',
        '#ffa800',
        '#ffd635',
        '#00a368',
        '#00cc78',
        '#7eed56',
        '#00756f',
        '#009eaa',
        '#2450a4',
        '#3690ea',
        '#51e9f4',
        '#493ac1',
        '#6a5cff',
        '#811e9f',
        '#b44ac0',
        '#ff3881',
        '#ff99aa',
        '#6d482f',
        '#9c6926',
        '#000000',
        '#898d90',
        '#d4d7d9',
    ];
    const WIDTH = 30;
    const HEIGHT = 30;

    protected static function display_color_box(int $colorid, array $attrs = [], string $contents = '&nbsp;') {
        $color = self::COLORS[$colorid % count(self::COLORS)];
        return html_writer::tag('td', $contents, $attrs);
    }

    public static function display_colors() {
        $rv = '<table class="mod_rplace_chooser clickable"><tr>';
        for ($i = 0; $i < count(self::COLORS); $i++) {
            $rv .= html_writer::tag('td', '&nbsp;', ['data-id' => $i]);
        }
        $rv .= '</tr></table>';
        return $rv;
    }

    public static function display_pattern(\stdClass $activityrecord, \cm_info $cm) {
        $rv = html_writer::start_tag('table', [
            'class' => 'mod_rplace_pattern clickable',
            'data-pattern' => $activityrecord->pattern ?? '',
        ]);
        for ($row = 0; $row < self::HEIGHT; $row++) {
            $rv .= '<tr>';
            for ($col = 0; $col < self::WIDTH; $col++) {
                $rv .= html_writer::tag('td', '&nbsp;', ['data-x' => $col, 'data-y' => $row]);
            }
            $rv .= '</tr>';
        }
        $rv .= '</table>';
        return $rv;
    }

    public static function save_color(\cm_info $cm, int $x, int $y, int $color) {
        global $DB;

        if ($x < 0 || $x >= api::WIDTH || $y < 0 || $y >= api::HEIGHT || $color < 0 || $color >= count(api::COLORS)) {
            return;
        }

        $instance = $DB->get_record('rplace', ['id' => $cm->instance], 'id, pattern', MUST_EXIST);
        $pattern = $instance->pattern ?? '';

        $patternrows = preg_split("/\\n/", $pattern);
        $newpattern = '';
        for ($i = 0; $i < api::HEIGHT; $i++) {
            $patternrow = $i < count($patternrows) ? $patternrows[$i] : '';
            for ($j = 0; $j < api::WIDTH; $j++) {
                $value = strlen($patternrow) > $j ? ord(substr($patternrow, $j, 1)) - ord('0') : 0;
                $value = max(0, min($value, count(self::COLORS) - 1));
                $value = ($j == $x && $i == $y) ? $color : $value;
                $newpattern .= chr(ord('0') + $value);
            }
            $newpattern .= "\n";
        }

        $DB->set_field('rplace', 'pattern', $newpattern, ['id' => $cm->instance]);
        \mod_rplace\event\pattern_updated::create_from_coordinates($cm, $x, $y, $color);
        $context = \context_module::instance($cm->id);
        $payload = [
                'pattern' => $newpattern,
                'updates' => ['x' => $x, 'y' => $y, 'color' => $color],
        ];
        foreach (enrol_get_course_users($cm->course, true) as $user) {
            \tool_realtime\api::notify($context, 'mod_rplace', 'pattern', $cm->id,
                (string) $user->id, $payload);
        }
    }
}
