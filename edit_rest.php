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

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/branchedquiz/lib.php');
require_once($CFG->dirroot . '/mod/branchedquiz/locallib.php');

// Initialise ALL the incoming parameters here, up front.
$quizid     = required_param('quizid', PARAM_INT);
$class      = required_param('class', PARAM_ALPHA);
$field      = optional_param('field', '', PARAM_ALPHA);
$instanceid = optional_param('instanceId', 0, PARAM_INT);
$sectionid  = optional_param('sectionId', 0, PARAM_INT);
$previousid = optional_param('previousid', 0, PARAM_INT);
$value      = optional_param('value', 0, PARAM_INT);
$column     = optional_param('column', 0, PARAM_ALPHA);
$id         = optional_param('id', 0, PARAM_INT);
$summary    = optional_param('summary', '', PARAM_RAW);
$sequence   = optional_param('sequence', '', PARAM_SEQUENCE);
$visible    = optional_param('visible', 0, PARAM_INT);
$pageaction = optional_param('action', '', PARAM_ALPHA); // Used to simulate a DELETE command.
$maxmark    = optional_param('maxmark', '', PARAM_FLOAT);
$newheading = optional_param('newheading', '', PARAM_TEXT);
$shuffle    = optional_param('newshuffle', 0, PARAM_INT);
$page       = optional_param('page', '', PARAM_INT);

$startslot   = optional_param('startSlot', '', PARAM_INT);
$endslot   = optional_param('endSlot', '', PARAM_INT);

$x   = optional_param('x', '', PARAM_INT);
$y   = optional_param('y', '', PARAM_INT);

$operator = optional_param('operator', '', PARAM_ALPHA);
$lowerbound    = optional_param('lowerbound', '', PARAM_FLOAT);
$upperbound    = optional_param('upperbound', '', PARAM_FLOAT);

$PAGE->set_url('/mod/branchedquiz/edit-rest.php',
        array('quizid' => $quizid, 'class' => $class));

// TODO: Consider using require_sesskey().
$quiz = $DB->get_record('branchedquiz', array('id' => $quizid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('branchedquiz', $quiz->id, $quiz->course);
$course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
require_login($course, false, $cm);

$quizobj = new quiz($quiz, $cm, $course);
$structure = $quizobj->get_structure();
$modcontext = context_module::instance($cm->id);

echo $OUTPUT->header(); // Send headers.

// OK, now let's process the parameters and do stuff
// MDL-10221 the DELETE method is not allowed on some web servers,
// so we simulate it with the action URL param.
$requestmethod = $_SERVER['REQUEST_METHOD'];
if ($pageaction == 'DELETE') {
    $requestmethod = 'DELETE';
}

switch($requestmethod) {
    case 'POST':
    case 'GET': // For debugging.
        switch ($class) {
            case 'resource':
                switch ($field) {
                    case 'move':
                        require_capability('mod/quiz:manage', $modcontext);
                        if (!$previousid) {
                            $section = $structure->get_section_by_id($sectionid);
                            if ($section->firstslot > 1) {
                                $previousid = $structure->get_slot_id_for_slot($section->firstslot - 1);
                                $page = $structure->get_page_number_for_slot($section->firstslot);
                            }
                        }
                        $structure->move_slot($id, $previousid, $page);
                        branchedquiz_delete_previews($quiz);
                        echo json_encode(array('visible' => true));
                        break;

                    case 'addedge':
                        require_capability('mod/quiz:manage', $modcontext);
                        $edgeid = branchedquiz_add_edge($quiz, $startslot, $endslot);
                        echo json_encode(array('id' => $edgeid));
                        break;

                    case 'togglemain':
                        require_capability('mod/quiz:manage', $modcontext);
                        $nodetype = branchedquiz_set_nodetype($quiz, $id, $value);
                        echo json_encode(array('nodetype' => $nodetype));
                        break;

                    case 'updateedge':
                        require_capability('mod/quiz:manage', $modcontext);

                        $edge = branchedquiz_update_edge($quiz, $id, $operator, $lowerbound, $upperbound);
                        echo json_encode($edge);
                        break;

                    case 'posnode':
                        require_capability('mod/quiz:manage', $modcontext);
                        $edgeid = branchedquiz_pos_node($quiz, $id, $x, $y);
                        echo json_encode(array('x' => $x, 'y' => $y));
                        break;

                    case 'getmaxmark':
                        require_capability('mod/quiz:manage', $modcontext);
                        $slot = $DB->get_record('quiz_slots', array('id' => $id), '*', MUST_EXIST);
                        echo json_encode(array('instancemaxmark' => quiz_format_question_grade($quiz, $slot->maxmark)));
                        break;

                    case 'updatemaxmark':
                        require_capability('mod/quiz:manage', $modcontext);
                        $slot = $structure->get_slot_by_id($id);
                        if ($structure->update_slot_maxmark($slot, $maxmark)) {
                            // Grade has really changed.
                            quiz_delete_previews($quiz);
                            quiz_update_sumgrades($quiz);
                            quiz_update_all_attempt_sumgrades($quiz);
                            quiz_update_all_final_grades($quiz);
                            quiz_update_grades($quiz, 0, true);
                        }
                        echo json_encode(array('maxmark' => $maxmark,
                            'instancemaxmark' => quiz_format_question_grade($quiz, $maxmark),
                                'newsummarks' => quiz_format_grade($quiz, $quiz->sumgrades)));
                        break;
                }
                break;
        }
        break;

    case 'DELETE':
        switch ($class) {
            case 'resource':
                require_capability('mod/quiz:manage', $modcontext);
                if (!$slot = $DB->get_record('quiz_slots', array('quizid' => $quiz->id, 'id' => $id))) {
                    throw new moodle_exception('AJAX commands.php: Bad slot ID '.$id);
                }
                $structure->remove_slot($slot->slot);
                branchedquiz_remove_node($quiz, $id);
                quiz_delete_previews($quiz);
                quiz_update_sumgrades($quiz);
                echo json_encode(array('newsummarks' => quiz_format_grade($quiz, $quiz->sumgrades),
                            'deleted' => true, 'newnumquestions' => $structure->get_question_count()));
                break;
            case 'edge':
                require_capability('mod/quiz:manage', $modcontext);
                branchedquiz_remove_edge($quiz, $id);
                echo json_encode(array('success' => 1));
                break;
        }
        break;
}
