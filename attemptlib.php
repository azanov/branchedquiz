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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');

class branchedquiz extends quiz {

    public function view_url() {
        global $CFG;
        return $CFG->wwwroot . '/mod/branchedquiz/view.php?id=' . $this->cm->id;
    }

    public function edit_url() {
        global $CFG;
        return $CFG->wwwroot . '/mod/branchedquiz/edit.php?cmid=' . $this->cm->id;
    }

    public function attempt_url($attemptid, $page = 0) {
        global $CFG;
        $url = $CFG->wwwroot . '/mod/branchedquiz/attempt.php?attempt=' . $attemptid;
        if ($page) {
            $url .= '&page=' . $page;
        }
        return $url;
    }

    public function start_attempt_url($page = 0) {
        $params = array('cmid' => $this->cm->id, 'sesskey' => sesskey());
        if ($page) {
            $params['page'] = $page;
        }
        return new moodle_url('/mod/branchedquiz/startattempt.php', $params);
    }

    public function summary_url($attemptid) {
        return new moodle_url('/mod/branchedquiz/summary.php', array('attempt' => $attemptid));
    }

    public function review_url($attemptid) {
        return new moodle_url('/mod/branchedquiz/review.php', array('attempt' => $attemptid));
    }

    public static function create($quizid, $userid = null) {
        global $DB;

        $quiz = quiz_access_manager::load_quiz_and_settings($quizid);
        $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('branchedquiz', $quiz->id, $course->id, false, MUST_EXIST);

        // Update quiz with override information.
        if ($userid) {
            $quiz = quiz_update_effective_access($quiz, $userid);
        }

        quiz_repaginate_questions($quiz->id, 1);

        return new branchedquiz($quiz, $cm, $course);
    }

    protected static function create_helper($conditions) {
        global $DB;

        $attempt = $DB->get_record('quiz_attempts', $conditions, '*', MUST_EXIST);
        $quiz = quiz_access_manager::load_quiz_and_settings($attempt->quiz);
        $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('branchedquiz', $quiz->id, $course->id, false, MUST_EXIST);

        // Update quiz with override information.
        $quiz = quiz_update_effective_access($quiz, $attempt->userid);

        return new quiz_attempt($attempt, $quiz, $cm, $course);
    }
}

class branchedquiz_attempt extends quiz_attempt {

    public function __construct($attempt, $quiz, $cm, $course, $loadquestions = true) {
        parent::__construct($attempt, $quiz, $cm, $course, $loadquestions);
        $this->quizobj = new branchedquiz($quiz, $cm, $course);
    }

    public function get_quba() {
        return $this->quba;
    }

    public function get_unformatted_question_mark($slot) {
        return $this->quba->get_question_mark($slot);
    }

    protected static function create_helper($conditions) {
        global $DB;

        $attempt = $DB->get_record('quiz_attempts', $conditions, '*', MUST_EXIST);
        $quiz = quiz_access_manager::load_quiz_and_settings($attempt->quiz);
        $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('branchedquiz', $quiz->id, $course->id, false, MUST_EXIST);

        // Update quiz with override information.
        $quiz = quiz_update_effective_access($quiz, $attempt->userid);

        return new branchedquiz_attempt($attempt, $quiz, $cm, $course);
    }

    public static function create($attemptid) {
        return self::create_helper(array('id' => $attemptid));
    }

    public function processattempt_url() {
        return new moodle_url('/mod/branchedquiz/processattempt.php');
    }

    public function summary_url() {
        return new moodle_url('/mod/branchedquiz/summary.php', array('attempt' => $this->attempt->id));
    }

    protected function page_and_question_url($script, $slot, $page, $showall, $thispage) {

        $defaultshowall = $this->get_default_show_all($script);
        if ($showall === null && ($page == 0 || $page == -1)) {
            $showall = $defaultshowall;
        }

        // Fix up $page.
        if ($page == -1) {
            if ($slot !== null && !$showall) {
                $page = $this->get_question_page($slot);
            } else {
                $page = 0;
            }
        }

        if ($showall) {
            $page = 0;
        }

        // Add a fragment to scroll down to the question.
        $fragment = '';
        if ($slot !== null) {
            if ($slot == reset($this->pagelayout[$page])) {
                // First question on page, go to top.
                $fragment = '#';
            } else {
                $fragment = '#q' . $slot;
            }
        }

        // Work out the correct start to the URL.
        if ($thispage == $page) {
            return new moodle_url($fragment);

        } else {
            $url = new moodle_url('/mod/branchedquiz/' . $script . '.php' . $fragment,
                    array('attempt' => $this->attempt->id));
            if ($page == 0 && $showall != $defaultshowall) {
                $url->param('showall', (int) $showall);
            } else if ($page > 0) {
                $url->param('page', $page);
            }
            return $url;
        }
    }

    public function get_question_status($slot, $showcorrectness) {
        return $this->quba->get_question_state_string($slot, false);
    }

    protected function number_questions() {
        $number = 1;
        foreach ($this->pagelayout as $page => $slots) {
            foreach ($slots as $slot) {
                if ($length = $this->is_real_question($slot)) {
                    $this->questionnumbers[$slot] = $number;
                    $number += $length;
                } else {
                    $this->questionnumbers[$slot] = get_string('infoshort', 'quiz');
                }
                $this->questionpages[$slot] = $page;
            }
        }
    }
}
