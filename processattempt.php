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
 * This page deals with processing responses during an attempt at a quiz.
 *
 * People will normally arrive here from a form submission on attempt.php or
 * summary.php, and once the responses are processed, they will be redirected to
 * attempt.php or summary.php.
 *
 * This code used to be near the top of attempt.php, if you are looking for CVS history.
 *
 * @package   mod_quiz
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/branchedquiz/locallib.php');
require_once($CFG->dirroot . '/mod/branchedquiz/attemptlib.php');

// Remember the current time as the time any responses were submitted
// (so as to make sure students don't get penalized for slow processing on this page).
$timenow = time();

// Get submitted parameters.
$attemptid     = required_param('attempt',  PARAM_INT);
$thispage      = optional_param('thispage', 0, PARAM_INT);
$nextpage      = optional_param('nextpage', 0, PARAM_INT);
$previous      = optional_param('previous',      false, PARAM_BOOL);
$next          = optional_param('next',          false, PARAM_BOOL);
$finishattempt = optional_param('finishattempt', false, PARAM_BOOL);
$timeup        = optional_param('timeup',        0,      PARAM_BOOL); // True if form was submitted by timer.
$scrollpos     = optional_param('scrollpos',     '',     PARAM_RAW);

$attemptobj = branchedquiz_attempt::create($attemptid);

//note: the first page in processattempt is 0, in questions however 1
// if page == -1, user gets  summary page
$slotid = page_to_slotid($attemptobj->get_quizobj(), $thispage+1);
$points = -1;
if ($slotid != -1) $points = $attemptobj->get_unformatted_question_mark(page_to_slot($attemptobj->get_quizobj(), $thispage+1));
$next_slotid = -1;

if (!is_null($points)) {
    // check if points don't matter  lowerbound == null && upperbound == null
    $edge = $DB->get_record_sql('SELECT * FROM {branchedquiz_edge} WHERE slotid = ? AND lowerbound IS NULL AND upperbound IS NULL', array($slotid));

    //if query is empty, then points matter
    if (!$edge) {
        $edge_eq = $DB->get_record_sql('SELECT * FROM {branchedquiz_edge} WHERE slotid = ? AND operator = ? AND upperbound = ? AND lowerbound = ?', array($slotid, "eq", $points, $points));
        $edge_lt = $DB->get_records_sql('SELECT * FROM {branchedquiz_edge} WHERE slotid = ? AND operator = ?', array($slotid, "le"));
        $edge_lt_eq = $DB->get_records_sql('SELECT * FROM {branchedquiz_edge} WHERE slotid = ? AND operator = ?', array($slotid, "lq"));

        // operator == equal
        if ($edge_eq){
            assert($edge_eq->upperbound == $edge_eq->lowerbound);
            $next_slotid = $edge_eq->next;

        }
        // operator == less than
        if ($edge_lt) {
            foreach ($edge_lt as $lt) {
                $up = $lt->upperbound;
                $low = $lt->lowerbound;
                // valid values for upperbound and lowerbound
                if (!is_null($low) && !is_null($up)) {
                    if ($points < $up && $points > $low) $next_slotid = $lt->next;
                    // no upperbound
                } else if (!is_null($low) && is_null($up)) {
                    if ($points > $low) $next_slotid = $lt->next;
                    // no lowerbound
                } else if (is_null($low) && !is_null($up)) {
                    if ($points < $up) $next_slotid = $lt->next;
                }
            }
        }
        //operator == less than or equal
        if ($edge_lt_eq) {
                foreach ($edge_lt_eq as $lt_eq) {
                    $up = $lt_eq->upperbound;
                    $low = $lt_eq->lowerbound;
                    // valid values for upperbound and lowerbound
                    if (!is_null($low) && !is_null($up)) {
                        if ($points <= $up && $points >= $low) $next_slotid = $lt_eq->next;
                        // no upperbound
                    } elseif (!is_null($low) && is_null($up)) {
                        if ($points >= $low) $next_slotid = $lt_eq->next;
                        // no lowerbound
                    } else if (is_null($low) && !is_null($up)) {
                        if ($points <= $up) $next_slotid = $lt_eq->next;
                    }
                }
        }
    } else {
        $next_slotid = $edge->next;
    }

    $branched_next = slotid_to_page($attemptobj->get_quizobj(), $next_slotid);

    if ($branched_next != -1) $branched_next -= 1;
}

// Set $nexturl now.
if ($next) {
    $page =   $branched_next;
    //$page =   $nextpage;
} else if ($previous && $thispage > 0) {
    $page = $thispage - 1;
} else {
    $page = $thispage;
}
if ($page == -1) {
    $nexturl = $attemptobj->summary_url();
} else {
    $nexturl = $attemptobj->attempt_url(null, $page);
    if ($scrollpos !== '') {
        $nexturl->param('scrollpos', $scrollpos);
    }
}

// Check login.
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
require_sesskey();

// Check that this attempt belongs to this user.
if ($attemptobj->get_userid() != $USER->id) {
    throw new moodle_quiz_exception($attemptobj->get_quizobj(), 'notyourattempt');
}

// Check capabilities.
if (!$attemptobj->is_preview_user()) {
    $attemptobj->require_capability('mod/quiz:attempt');
}

// If the attempt is already closed, send them to the review page.
if ($attemptobj->is_finished()) {
    throw new moodle_quiz_exception($attemptobj->get_quizobj(),
            'attemptalreadyclosed', null, $attemptobj->review_url());
}

// Process the attempt, getting the new status for the attempt.
$status = $attemptobj->process_attempt($timenow, $finishattempt, $timeup, $thispage);

if ($status == quiz_attempt::OVERDUE) {
    redirect($attemptobj->summary_url());
} else if ($status == quiz_attempt::IN_PROGRESS) {
    redirect($nexturl);
} else {
    // Attempt abandoned or finished.
    redirect($attemptobj->review_url());
}
