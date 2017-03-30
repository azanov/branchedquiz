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

require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->dirroot.'/mod/branchedquiz/lib.php');
require_once($CFG->dirroot.'/mod/branchedquiz/attemptlib.php');

function branchedquiz_create_attempt(quiz $quizobj, $attemptnumber, $lastattempt, $timenow, $ispreview = false, $userid = null) {
    global $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $quiz = $quizobj->get_quiz();
    if ($quiz->sumgrades < 0.000005 && $quiz->grade > 0.000005) {
        throw new moodle_exception('cannotstartgradesmismatch', 'quiz',
                new moodle_url('/mod/branchedquiz/view.php', array('q' => $quiz->id)),
                    array('grade' => quiz_format_grade($quiz, $quiz->grade)));
    }

    if ($attemptnumber == 1 || !$quiz->attemptonlast) {
        // We are not building on last attempt so create a new attempt.
        $attempt = new stdClass();
        $attempt->quiz = $quiz->id;
        $attempt->userid = $userid;
        $attempt->preview = 0;
        $attempt->layout = '';
    } else {
        // Build on last attempt.
        if (empty($lastattempt)) {
            print_error('cannotfindprevattempt', 'quiz');
        }
        $attempt = $lastattempt;
    }

    $attempt->attempt = $attemptnumber;
    $attempt->timestart = $timenow;
    $attempt->timefinish = 0;
    $attempt->timemodified = $timenow;
    $attempt->state = quiz_attempt::IN_PROGRESS;
    $attempt->currentpage = 0;
    $attempt->sumgrades = null;

    // If this is a preview, mark it as such.
    if ($ispreview) {
        $attempt->preview = 1;
    }

    $timeclose = $quizobj->get_access_manager($timenow)->get_end_time($attempt);
    if ($timeclose === false || $ispreview) {
        $attempt->timecheckstate = null;
    } else {
        $attempt->timecheckstate = $timeclose;
    }

    return $attempt;
}

function branchedquiz_save_best_grade($quiz, $userid = null, $attempts = array()) {
    global $DB, $OUTPUT, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if (!$attempts) {
        // Get all the attempts made by the user.
        $attempts = quiz_get_user_attempts($quiz->id, $userid);
    }

    // Calculate the best grade.
    $bestgrade = quiz_calculate_best_grade($quiz, $attempts);
    $bestgrade = quiz_rescale_grade($bestgrade, $quiz, false);

    // Save the best grade in the database.
    if (is_null($bestgrade)) {
        $DB->delete_records('quiz_grades', array('quiz' => $quiz->id, 'userid' => $userid));

    } else if ($grade = $DB->get_record('quiz_grades',
            array('quiz' => $quiz->id, 'userid' => $userid))) {
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->update_record('quiz_grades', $grade);

    } else {
        $grade = new stdClass();
        $grade->quiz = $quiz->id;
        $grade->userid = $userid;
        $grade->grade = $bestgrade;
        $grade->timemodified = time();
        $DB->insert_record('quiz_grades', $grade);
    }

    branchedquiz_update_grades($quiz, $userid);
}

function branchedquiz_delete_attempt($attempt, $quiz) {
    global $DB;
    if (is_numeric($attempt)) {
        if (!$attempt = $DB->get_record('quiz_attempts', array('id' => $attempt))) {
            return;
        }
    }

    if ($attempt->quiz != $quiz->id) {
        debugging("Trying to delete attempt $attempt->id which belongs to quiz $attempt->quiz " .
                "but was passed quiz $quiz->id.");
        return;
    }

    if (!isset($quiz->cmid)) {
        $cm = get_coursemodule_from_instance('branchedquiz', $quiz->id, $quiz->course);
        $quiz->cmid = $cm->id;
    }

    question_engine::delete_questions_usage_by_activity($attempt->uniqueid);
    $DB->delete_records('quiz_attempts', array('id' => $attempt->id));

    // Log the deletion of the attempt if not a preview.
    if (!$attempt->preview) {
        $params = array(
            'objectid' => $attempt->id,
            'relateduserid' => $attempt->userid,
            'context' => context_module::instance($quiz->cmid),
            'other' => array(
                'quizid' => $quiz->id
            )
        );
        $event = \mod_quiz\event\attempt_deleted::create($params);
        $event->add_record_snapshot('quiz_attempts', $attempt);
        $event->trigger();
    }

    // Search quiz_attempts for other instances by this user.
    // If none, then delete record for this quiz, this user from quiz_grades
    // else recalculate best grade.
    $userid = $attempt->userid;
    if (!$DB->record_exists('quiz_attempts', array('userid' => $userid, 'quiz' => $quiz->id))) {
        $DB->delete_records('quiz_grades', array('userid' => $userid, 'quiz' => $quiz->id));
    } else {
        branchedquiz_save_best_grade($quiz, $userid);
    }

    branchedquiz_update_grades($quiz, $userid);
}

function branchedquiz_delete_previews($quiz, $userid = null) {
    global $DB;
    $conditions = array('quiz' => $quiz->id, 'preview' => 1);
    if (!empty($userid)) {
        $conditions['userid'] = $userid;
    }
    $previewattempts = $DB->get_records('quiz_attempts', $conditions);
    foreach ($previewattempts as $attempt) {
        branchedquiz_delete_attempt($attempt, $quiz);
    }
}

function branchedquiz_prepare_and_start_new_attempt(branchedquiz $quizobj, $attemptnumber, $lastattempt) {
    global $DB, $USER;

    // Delete any previous preview attempts belonging to this user.
    branchedquiz_delete_previews($quizobj->get_quiz(), $USER->id);

    $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
    $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

    // Create the new attempt and initialize the question sessions
    $timenow = time(); // Update time now, in case the server is running really slowly.
    $attempt = branchedquiz_create_attempt($quizobj, $attemptnumber, $lastattempt, $timenow, $quizobj->is_preview_user());

    if (!($quizobj->get_quiz()->attemptonlast && $lastattempt)) {
        $attempt = quiz_start_new_attempt($quizobj, $quba, $attempt, $attemptnumber, $timenow);
    } else {
        $attempt = quiz_start_attempt_built_on_last($quba, $attempt, $lastattempt);
    }

    $transaction = $DB->start_delegated_transaction();

    $attempt = quiz_attempt_save_started($quizobj, $quba, $attempt);

    $transaction->allow_commit();

    return $attempt;
}

function branchedquiz_add_quiz_question($questionid, $quiz, $page = 0, $maxmark = null) {
    global $DB;
    $slots = $DB->get_records('quiz_slots', array('quizid' => $quiz->id),
            'slot', 'questionid, slot, page, id');

    if (array_key_exists($questionid, $slots)) {
        return false;
    }

    $trans = $DB->start_delegated_transaction();

    $maxpage = 1;
    $numonlastpage = 0;
    foreach ($slots as $slot) {
        if ($slot->page > $maxpage) {
            $maxpage = $slot->page;
            $numonlastpage = 1;
        } else {
            $numonlastpage += 1;
        }
    }

    // Add the new question instance.
    $slot = new stdClass();
    $slot->quizid = $quiz->id;
    $slot->questionid = $questionid;

    if ($maxmark !== null) {
        $slot->maxmark = $maxmark;
    } else {
        $slot->maxmark = $DB->get_field('question', 'defaultmark', array('id' => $questionid));
    }

    $lastslot = end($slots);
    if ($lastslot) {
        $slot->slot = $lastslot->slot + 1;
    } else {
        $slot->slot = 1;
    }

    $slot->page = $maxpage + 1;

    $DB->insert_record('quiz_slots', $slot);

    $trans->allow_commit();

    $trans = $DB->start_delegated_transaction();
    $slot = $DB->get_record_sql('SELECT * FROM {quiz_slots} WHERE quizid = ? AND questionid = ?', array($quiz->id, $questionid));
    $node = new stdClass();
    $node->quizid = $quiz->id;
    $node->slotid = $slot->id;
    $node->nodetype = 0;
    $node->x = 30;
    $node->y = 30 * $slot->slot + 50 * ($slot->slot - 1);

    $DB->insert_record('branchedquiz_node', $node);
    $trans->allow_commit();
}

/**
 * returns the slotid of the given page
 * @param $quizobj
 * @param $page
 * @return int
 */
function page_to_slotid($quizobj, $page) {
    $quizobj->preload_questions();
    $quizobj->load_questions();

    $qs = $quizobj->get_questions();
    $slotid = -1;

    foreach (array_keys($qs) as $id) {
        if ($qs[$id]->page == $page) {
            $slotid = $qs[$id]->slotid;
            break;
        }
    }

    return $slotid;
}

/**
 * returns the slot of the given page
 * @param $quizobj
 * @param $page
 * @return int
 */
function page_to_slot($quizobj, $page) {
    $quizobj->preload_questions();
    $quizobj->load_questions();

    $qs = $quizobj->get_questions();
    $slot = -1;

    foreach (array_keys($qs) as $id) {
        if ($qs[$id]->page == $page) {
            $slot = $qs[$id]->slot;
        }
    }

    return $slot;
}

/**
 * returns the page number of the slotid
 * @param $quizobj
 * @param $slotid
 * @return int
 */
function slotid_to_page($quizobj, $slotid) {
    $quizobj->preload_questions();
    $quizobj->load_questions();

    $qs = $quizobj->get_questions();
    $page = -1;

    foreach (array_keys($qs) as $id) {
        if ($qs[$id]->slotid == $slotid) {
            $page = $qs[$id]->page;
        }
    }

    return $page;
}

/**
 * retuns slots of questions that the user has actually seen in current path,
 * but not in the order in which he saw the questions
 * @param $attemptobj
 * @return array of slots that the user has actually seen in current path
 */

function get_current_path_slots($attemptobj) {

    $quizobj = $attemptobj->get_quizobj();
    $quizobj->preload_questions();
    $quizobj->load_questions();

    $qs = $quizobj->get_questions();

    $slots = array();

    foreach (array_keys($qs) as $id) {
        $tmp = ($attemptobj->get_question_mark($qs[$id]->slot));
        if (!empty($tmp)) {
            array_push($slots, $qs[$id]->slot);
        }
    }

    return $slots;
}

/**
 * used in summary table, to pass the summary table on questions in current path
 * @param $attemptobj
 * @return int sum of max grades of all questions in current path
 */

function get_sum_max_grades($attemptobj) {

    global $DB;
    $slots = get_current_path_slots($attemptobj);

    $quba = $attemptobj->get_quba();

    $sum = 0;
    foreach ($slots as $slot) {

        $node = $DB->get_record_sql('SELECT * FROM {branchedquiz_node} WHERE slotid = ?',
            array(slot_to_slotid($attemptobj->get_quizobj(), $slot)));
        // Main questions are 0 and sub questions are 1.
        // Our max grade is the sum of all main questions.
        if ($node->nodetype == 0) {
            $sum += $quba->get_question_max_mark($slot);
        }
    }

    return $sum;
}

/**Converts from slot to slotids
 * @param $quizobj
 * @param $slot
 * @return int $slotid
 */
function slot_to_slotid($quizobj, $slot) {
    $quizobj->preload_questions();
    $quizobj->load_questions();

    $qs = $quizobj->get_questions();
    $slotid = -1;

    foreach (array_keys($qs) as $id) {
        if ($qs[$id]->slot == $slot) {
            $slotid = $qs[$id]->slotid;
        }
    }

    return $slotid;
}


/**
 * Convert the raw grade stored in $attempt into a grade out of the maximum
 * grade for this quiz.
 *
 * @param float $rawgrade the unadjusted grade, fof example $attempt->sumgrades
 * @param object $quiz the quiz object. Only the fields grade, sumgrades and decimalpoints are used.
 * @param bool|string $format whether to format the results for display
 *      or 'question' to format a question grade (different number of decimal places.
 * @param $summax sum of max grades for all slots in current path
 * @return float|string the rescaled grade, or null/the lang string 'notyetgraded'
 *      if the $grade is null.
 */
function branchedquiz_rescale_grade($rawgrade, $quiz, $summax, $format = true) {

    if (is_null($rawgrade)) {
        $grade = null;
    } else if ($summax >= 0.000005) {
        $grade = $rawgrade * $quiz->grade / $summax;
    } else {
        $grade = 0;
    }
    if ($format === 'question') {
        $grade = quiz_format_question_grade($quiz, $grade);
    } else if ($format) {
        $grade = quiz_format_grade($quiz, $grade);
    }
    return $grade;
}

function branchedquiz_set_grade($newgrade, $quiz) {
    global $DB;
    // This is potentially expensive, so only do it if necessary.
    if (abs($quiz->grade - $newgrade) < 1e-7) {
        // Nothing to do.
        return true;
    }
    $oldgrade = $quiz->grade;
    $quiz->grade = $newgrade;
    // Use a transaction, so that on those databases that support it, this is safer.
    $transaction = $DB->start_delegated_transaction();
    // Update the quiz table.
    $DB->set_field('quiz', 'grade', $newgrade, array('id' => $quiz->instance));
    if ($oldgrade < 1) {
        // If the old grade was zero, we cannot rescale, we have to recompute.
        // We also recompute if the old grade was too small to avoid underflow problems.
        quiz_update_all_final_grades($quiz);
    } else {
        // We can rescale the grades efficiently.
        $timemodified = time();
        $DB->execute("
                UPDATE {quiz_grades}
                SET grade = ? * grade, timemodified = ?
                WHERE quiz = ?
        ", array($newgrade / $oldgrade, $timemodified, $quiz->id));
    }
    if ($oldgrade > 1e-7) {
        // Update the quiz_feedback table.
        $factor = $newgrade / $oldgrade;
        $DB->execute("
                UPDATE {quiz_feedback}
                SET mingrade = ? * mingrade, maxgrade = ? * maxgrade
                WHERE quizid = ?
        ", array($factor, $factor, $quiz->id));
    }
    // Update grade item and send all grades to gradebook.
    branchedquiz_grade_item_update($quiz);
    branchedquiz_update_grades($quiz);
    $transaction->allow_commit();
    return true;
}

function branchedquiz_add_edge($quiz, $startslot, $endslot) {
    global $DB;
    $edge = new stdClass();
    $edge->slotid = $startslot;
    $edge->next = $endslot;
    $edge->operator = '';
    $edge->feedbacktext = '';
    return $DB->insert_record('branchedquiz_edge', $edge);
}

function branchedquiz_remove_node($quiz, $slotid) {
    global $DB;
    $DB->delete_records('branchedquiz_node', array('quizid' => $quiz->id, 'id' => $slotid));
    $DB->delete_records('branchedquiz_edge', array('slotid' => $slotid));
    $DB->delete_records('branchedquiz_edge', array('next' => $slotid));
}

function branchedquiz_pos_node($quiz, $slot, $x, $y) {
    global $DB;
    $node = $DB->get_record('branchedquiz_node', array('quizid' => $quiz->id, 'slotid' => $slot));
    $node->x = $x;
    $node->y = $y;
    $DB->update_record('branchedquiz_node', $node);
}

function branchedquiz_set_nodetype($quiz, $slot, $nodetype) {
    global $DB;
    $node = $DB->get_record('branchedquiz_node', array('quizid' => $quiz->id, 'slotid' => $slot));
    $node->nodetype = $nodetype;
    $DB->update_record('branchedquiz_node', $node);
}

define("OPERATOR_EQUAL", "eq");
define("OPERATOR_LESS", "le");
define("OPERATOR_LESS_OR_EQUAL", "lq");
define("OPERATOR_UI_ONLY_MAX", "max");
define("OPERATOR_UI_ONLY_MIN", "min");
define("OPERATOR_UI_ONLY_LESS", "less");
define("OPERATOR_UI_ONLY_MORE", "more");

function branchedquiz_update_edge($quiz, $id, $operator, $lowerbound, $upperbound) {
    global $DB;
    $edge = $DB->get_record('branchedquiz_edge', array('id' => $id));

    if (empty($operator) || $operator == '') {
        // All results.
        $edge->operator = $operator;
        $edge->lowerbound = null;
        $edge->upperbound = null;
    } else if ($operator == OPERATOR_EQUAL) {

        // Fixed result only.
        $edge->operator = $operator;
        $edge->lowerbound = $lowerbound;
        $edge->upperbound = $lowerbound;

    } else if ($operator == OPERATOR_UI_ONLY_MIN) {

        $edge->operator = OPERATOR_LESS_OR_EQUAL;
        $edge->lowerbound = $lowerbound;
        $edge->upperbound = null;

    } else if ($operator == OPERATOR_UI_ONLY_MAX) {

        $edge->operator = OPERATOR_LESS_OR_EQUAL;
        $edge->lowerbound = null;
        $edge->upperbound = $upperbound;

    } else if ($operator == OPERATOR_LESS) {

        $edge->operator = OPERATOR_LESS;
        $edge->lowerbound = $lowerbound;
        $edge->upperbound = $upperbound;

    } else if ($operator == OPERATOR_LESS_OR_EQUAL) {

        $edge->operator = OPERATOR_LESS_OR_EQUAL;
        $edge->lowerbound = $lowerbound;
        $edge->upperbound = $upperbound;

    } else if ($operator == OPERATOR_UI_ONLY_LESS) {

        $edge->operator = OPERATOR_LESS;
        $edge->lowerbound = null;
        $edge->upperbound = $upperbound;

    } else if ($operator == OPERATOR_UI_ONLY_MORE) {

        $edge->operator = OPERATOR_LESS;
        $edge->lowerbound = $lowerbound;
        $edge->upperbound = null;

    }

    $DB->update_record('branchedquiz_edge', $edge);

    $edge->operator = $operator;
    return $edge;
}

function branchedquiz_remove_edge($quiz, $id) {
    global $DB;
    $DB->delete_records('branchedquiz_edge', array('id' => $id));
}