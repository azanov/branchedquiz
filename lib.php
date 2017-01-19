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
 * Quiz activity version information.
 *
 * @package   mod_flowquiz
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/lib.php');

function branchedquiz_add_instance($quiz) {
	print_r($quiz);

	global $DB;
    $cmid = $quiz->coursemodule;

    $quiz->quizid = quiz_add_instance($quiz);
    $quiz->created = time();
    unset($quiz->id);

    // Try to store it in the database.
    return $DB->insert_record('branchedquiz', $quiz);

}

function branchedquiz_update_instance($quiz, $mform) {
	return quiz_update_instance($quiz, $mform);
}

function branchedquiz_delete_instance($id) {
	return quiz_delete_instance($id);
}

function branchedquiz_supports($feature) {
    return quiz_supports($feature);
}