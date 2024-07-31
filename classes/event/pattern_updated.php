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

namespace mod_rplace\event;

/**
 * Event pattern_updated
 *
 * @package    mod_rplace
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pattern_updated extends \core\event\base {

    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'rplace';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $x = $this->other['x'];
        $y = $this->other['y'];
        $color = $this->other['color'];
        return "The user with id '$this->userid' painted the ($x, $y) point with color $color " .
            "in 'rplace' activity with course module id '$this->contextinstanceid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('patternupdated', 'mod_rplace');
    }

    /**
     * Creates an instance of event
     *
     * @param \cm_info|\stdClass $cm
     * @return pattern_updated
     */
    public static function create_from_coordinates($cm, int $x, int $y, int $color) {
        /** @var pattern_updated $event */
        $event = self::create([
            'objectid' => $cm->instance,
            'context' => \context_module::instance($cm->id),
            'other' => ['x' => $x, 'y' => $y, 'color' => $color],
        ]);
        $event->add_record_snapshot('course_modules', $cm);
        return $event;
    }
}
