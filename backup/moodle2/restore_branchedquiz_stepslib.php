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

/**
 * Structure step to restore one adaptivequiz activity
 *
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_adaptivequiz_activity_structure_step extends restore_questions_activity_structure_step {

    /**
     * Define the a structure for restoring the activity
     * @return backup_nested_element the $activitystructure wrapped by the common 'activity' element
     */
    protected function define_structure() {
        $paths = array();
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the adaptivequiz element
     * @param stdClass an object whose properties are nodes in the adatpviequiz structure
     */
    protected function process_adaptivequiz($data) {
    }

    /**
     * Process the activity instance to question categories relation structure\
     * @param stdClass an object whose properties are nodes in the adatpviequiz_question structure
     */
    protected function process_adaptivequiz_question($data) {

    }

    /**
     * Process the activity instance to question categories relation structure
     * @param stdClass an object whose properties are nodes in the adatpviequiz_attempt structure
     */
    protected function process_adaptivequiz_attempt($data) {

    }

    /**
     * This function assigns the new question usage by activity id to the attempt
     * @param int $newusageid a new question usage by activity id
     */
    protected function inform_new_usage_id($newusageid) {

    }

    /**
     * This function adds any files assocaited with the intro field after the restore process has run
     */
    protected function after_execute() {

    }
}