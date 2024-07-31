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

namespace mod_rplace\external;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_api;
use core_external\external_value;
use mod_rplace\api;

/**
 * Implementation of web service mod_rplace_paint
 *
 * @package    mod_rplace
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class paint extends external_api {

    /**
     * Describes the parameters for mod_rplace_paint
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'x' => new external_value(PARAM_INT, 'X position'),
            'y' => new external_value(PARAM_INT, 'Y position'),
            'color' => new external_value(PARAM_INT, 'Color'),
        ]);
    }

    /**
     * Implementation of web service mod_rplace_paint
     *
     * @param mixed $param1
     */
    public static function execute($cmid, $x, $y, $color) {
        global $DB;

        // Parameter validation.
        ['cmid' => $cmid, 'x' => $x, 'y' => $y, 'color' => $color] = self::validate_parameters(
            self::execute_parameters(),
            ['cmid' => $cmid, 'x' => $x, 'y' => $y, 'color' => $color]
        );

        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'rplace');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/rplace:paint', $context);

        api::save_color($cm, $x, $y, $color);

        return [];
    }

    /**
     * Describe the return structure for mod_rplace_paint
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([]);
    }
}
