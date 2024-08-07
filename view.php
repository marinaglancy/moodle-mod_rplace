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
 * View Rplace instance
 *
 * @package    mod_rplace
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_rplace\api;

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$r = optional_param('r', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('rplace', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('rplace', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('rplace', ['id' => $r], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('rplace', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

\mod_rplace\event\course_module_viewed::create_from_record($moduleinstance, $cm, $course)->trigger();

$PAGE->set_url('/mod/rplace/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));

// Subscribe to realtime notifications.
(new \tool_realtime\channel($PAGE->context, 'mod_rplace', 'pattern', $PAGE->cm->id))->subscribe();

$PAGE->requires->js_call_amd('mod_rplace/rplace', 'init', [$cm->id, api::COLORS]);

echo $OUTPUT->header();

if (has_capability('mod/rplace:paint', $PAGE->context)) {
    echo html_writer::tag('p', get_string('pickacolor', 'rplace') . ':');
    echo api::display_color_picker();
}

echo html_writer::tag('p', get_string('clicktodraw', 'rplace') . ':', ['class' => 'pt-4']);
echo html_writer::div(api::display_canvas($moduleinstance, $PAGE->cm));

if (has_capability('mod/rplace:paint', $PAGE->context)) {
    echo html_writer::tag('p',
    html_writer::checkbox('instantfeedback', '1', true,
        get_string('instantfeedback', 'rplace'),
        ['data-purpose' => 'mod_rplace_instantfeedback', 'class' => 'mr-2']),
    );
}

if (has_capability('mod/rplace:clearall', $PAGE->context)) {
    echo html_writer::tag('div',
        $OUTPUT->single_button('#', get_string('clearall', 'rplace'), 'get', ['data-action' => 'clearall']),
        ['class' => 'pt-2 mod_rplace_actions']);
}

echo $OUTPUT->footer();
