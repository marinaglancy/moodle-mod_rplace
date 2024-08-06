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
 * Allows to draw pattern
 *
 * @module     mod_rplace/rplace
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Ajax from 'core/ajax';
import * as PubSub from 'core/pubsub';
import * as RealTimeEvents from 'tool_realtime/events';
import * as Notification from 'core/notification';

const SELECTORS = {
    PATTERNTABLE: '.mod_rplace_pattern',
    CHOOSERTABLE: '.mod_rplace_chooser',
    PATTERNTD: '.mod_rplace_pattern td',
    CLICKABLEPATTERNTD: '.mod_rplace_pattern.clickable td',
    CHOOSERTD: '.mod_rplace_chooser td',
    CLICKABLECHOOSERTD: '.mod_rplace_chooser.clickable td',
    CLEARALL: '.mod_rplace_actions [data-action="clearall"]',
    FEEDBACKCHECKBOX: '[data-purpose="mod_rplace_instantfeedback"]',
};

let colors = ['#ffffff', '#000000'];
let currentColor = 0;

const setBgColor = (el, colorId) => {
    let style = 'background-color: ' + colors[colorId];
    if (colors[colorId] === '#000000') {
        style += '; color: #ffffff';
    }
    el.style = style;
};

const redraw = (pattern) => {
    const patternrows = ('' + pattern).split(/\n/);
    document.querySelectorAll(SELECTORS.PATTERNTD).forEach(el => {
        const x = parseInt(el.dataset.x);
        const y = parseInt(el.dataset.y);
        let value = (patternrows[y] ?? '').charCodeAt(x);
        value = (isNaN(value) ? 48 : value) - 48;
        setBgColor(el, value);
    });
};

const setCurrentColor = (colorId) => {
    document.querySelectorAll(SELECTORS.CLICKABLECHOOSERTD).forEach(el => {
        const id = parseInt(el.dataset.id);
        el.innerHTML = (id === colorId) ? 'X' : '&nbsp;';
    });
    currentColor = colorId;
};

export const init = (cmid, colorset) => {
    colors = colorset;
    setCurrentColor(Math.floor(Math.random() * colors.length));

    const patternTable = document.querySelector(SELECTORS.PATTERNTABLE);
    if (!patternTable) {
        return;
    }

    const initalpattern = patternTable.dataset.pattern;
    redraw(initalpattern);

    document.querySelectorAll(SELECTORS.CHOOSERTD).forEach(el => {
        setBgColor(el, parseInt(el.dataset.id));
    });

    document.querySelectorAll(SELECTORS.CLICKABLEPATTERNTD).forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            if (document.querySelector(SELECTORS.FEEDBACKCHECKBOX)?.checked) {
                // Change the cell color instantly, without waiting for update from server.
                setBgColor(el, currentColor);
            }
            Ajax.call([{
                methodname: 'mod_rplace_paint',
                args: {
                    cmid: parseInt(cmid), x: parseInt(el.dataset.x), y: parseInt(el.dataset.y), color: currentColor
                }
            }]);
        });
    });

    document.querySelectorAll(SELECTORS.CLICKABLECHOOSERTD).forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            setCurrentColor(parseInt(el.dataset.id));
        });
    });

    PubSub.subscribe(RealTimeEvents.CONNECTION_LOST, (e) => {
        window.console.log('Error', e);
        Notification.exception({
            name: 'Error',
            message: 'Something went wrong, please refresh the page'});
    });

    PubSub.subscribe(RealTimeEvents.EVENT, (data) => {
        const {component, area, itemid, payload} = data;
        if (!payload || component != 'mod_rplace' || area != 'pattern' || itemid != cmid) {
            return;
        }

        const updates = data.payload.updates;
        const el = updates ? document.querySelector(SELECTORS.CLICKABLEPATTERNTD +
            `[data-x="${updates.x}"][data-y="${updates.y}"]`) : null;
        if (el) {
            setBgColor(el, updates.color);
        } else {
            const pattern = data.payload.pattern;
            if (pattern) {
                redraw(pattern);
            }
        }
    });

    document.querySelectorAll(SELECTORS.CLEARALL).forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            Ajax.call([{
                methodname: 'mod_rplace_paint',
                args: {
                    cmid: parseInt(cmid), x: -1, y: -1, color: 0
                }
            }]);
        });
    });
};
