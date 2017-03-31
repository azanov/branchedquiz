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
 * @copyright  2017 onwards Dominik Wittenberg, Paul Youssef, Pavel Azanov, Alessandro Noli, Robin Voigt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_branchedquiz\question\bank;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/quiz/classes/question/bank/custom_view.php');

class custom_view extends \mod_quiz\question\bank\custom_view {

    public function add_to_quiz_url($questionid) {
        $params = $this->baseurl->params();
        $params['addquestion'] = $questionid;
        $params['sesskey'] = sesskey();
        return new \moodle_url('/mod/branchedquiz/edit.php', $params);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function display_options_form($showquestiontext, $scriptpath = '/mod/mod_quiz/edit.php', $showtextoption = false) {
        // Override script path.
        parent::display_options_form($showquestiontext, '/mod/mod_branchedquiz/edit.php', $showtextoption);
    }

}
