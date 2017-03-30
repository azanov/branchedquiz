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
 * @package    mod_branchedquiz
 * @copyright  2017 onwards Dominik Wittenberg, Paul Youssef, Pavel Azanov, Allessandro Oxymora, Robin Voigt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/mod_form.php');


/**
 * Settings form for the quiz module.
 *
 * @copyright  2006 Jamie Pratt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_branchedquiz_mod_form extends mod_quiz_mod_form {

    protected function definition() {
        global $PAGE;

        parent::definition();
        $mform = $this->_form;
        $mform->setDefault('questionsperpage', 1);
        $mform->setDefault('navmethod', 'free');
        $mform->setDefault('preferredbehaviour', 'immediatefeedback');

        $PAGE->requires->yui_module('moodle-mod_branchedquiz-modform', 'M.mod_branchedquiz.modform.init');
    }

}
