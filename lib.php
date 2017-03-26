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
require_once($CFG->dirroot . '/mod/branchedquiz/locallib.php');

function branchedquiz_add_instance($quiz) {
    return quiz_add_instance($quiz);
}

function branchedquiz_update_instance($quiz, $mform) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');

    // Process the options from the form.
    $result = quiz_process_options($quiz);
    if ($result && is_string($result)) {
        return $result;
    }

    // Get the current value, so we can see what changed.
    $oldquiz = $DB->get_record('quiz', array('id' => $quiz->instance));

    // We need two values from the existing DB record that are not in the form,
    // in some of the function calls below.
    $quiz->sumgrades = $oldquiz->sumgrades;
    $quiz->grade     = $oldquiz->grade;

    // Update the database.
    $quiz->id = $quiz->instance;
    $DB->update_record('quiz', $quiz);

    // Do the processing required after an add or an update.
    quiz_after_add_or_update($quiz);

    if ($oldquiz->grademethod != $quiz->grademethod) {
        quiz_update_all_final_grades($quiz);
        quiz_update_grades($quiz);
    }

    $quizdateschanged = $oldquiz->timelimit != $quiz->timelimit
                     || $oldquiz->timeclose != $quiz->timeclose
                     || $oldquiz->graceperiod != $quiz->graceperiod;
    if ($quizdateschanged) {
        quiz_update_open_attempts(array('quizid' => $quiz->id));
    }

    // Delete any previous preview attempts.
    branchedquiz_delete_previews($quiz);

    // Repaginate, if asked to.
    if (!empty($quiz->repaginatenow)) {
        quiz_repaginate_questions($quiz->id, $quiz->questionsperpage);
    }

    return true;
}

function branchedquiz_delete_instance($id) {
    global $DB;

    $slots = $DB->get_records_sql('SELECT * FROM {quiz_slots} WHERE quizid = ?', array($id));

    foreach ($slots as $slot) {
        $DB->delete_records('branchedquiz_edge', array('slotid' => $slot->id));
    }

    $DB->delete_records('branchedquiz_node', array('quizid' => $id));

    $result = quiz_delete_instance($id);

    return $result;
}

function branchedquiz_supports($feature) {
    return quiz_supports($feature);
}

function branchedquiz_extend_settings_navigation($settings, $quiznode) {
    global $PAGE, $CFG;

    // Require {@link questionlib.php}
    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $quiznode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    if (has_capability('mod/quiz:manageoverrides', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/branchedquiz/overrides.php', array('cmid' => $PAGE->cm->id));
        $node = navigation_node::create(get_string('groupoverrides', 'quiz'),
                new moodle_url($url, array('mode' => 'group')),
                navigation_node::TYPE_SETTING, null, 'mod_quiz_groupoverrides');
        $quiznode->add_node($node, $beforekey);

        $node = navigation_node::create(get_string('useroverrides', 'quiz'),
                new moodle_url($url, array('mode' => 'user')),
                navigation_node::TYPE_SETTING, null, 'mod_quiz_useroverrides');
        $quiznode->add_node($node, $beforekey);
    }

    if (has_capability('mod/quiz:manage', $PAGE->cm->context)) {
        $node = navigation_node::create(get_string('editquiz', 'quiz'),
                new moodle_url('/mod/branchedquiz/edit.php', array('cmid' => $PAGE->cm->id)),
                navigation_node::TYPE_SETTING, null, 'mod_quiz_edit',
                new pix_icon('t/edit', ''));
        $quiznode->add_node($node, $beforekey);
    }

    if (has_capability('mod/quiz:preview', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/branchedquiz/startattempt.php',
                array('cmid' => $PAGE->cm->id, 'sesskey' => sesskey()));
        $node = navigation_node::create(get_string('preview', 'quiz'), $url,
                navigation_node::TYPE_SETTING, null, 'mod_quiz_preview',
                new pix_icon('i/preview', ''));
        $quiznode->add_node($node, $beforekey);
    }

    if (has_any_capability(array('mod/quiz:viewreports', 'mod/quiz:grade'), $PAGE->cm->context)) {
        require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
        $reportlist = quiz_report_list($PAGE->cm->context);

        $url = new moodle_url('/mod/branchedquiz/report.php',
                array('id' => $PAGE->cm->id, 'mode' => reset($reportlist)));
        $reportnode = $quiznode->add_node(navigation_node::create(get_string('results', 'quiz'), $url,
                navigation_node::TYPE_SETTING,
                null, null, new pix_icon('i/report', '')), $beforekey);

        foreach ($reportlist as $report) {
            $url = new moodle_url('/mod/branchedquiz/report.php',
                    array('id' => $PAGE->cm->id, 'mode' => $report));
            $reportnode->add_node(navigation_node::create(get_string($report, 'quiz_'.$report), $url,
                    navigation_node::TYPE_SETTING,
                    null, 'quiz_report_' . $report, new pix_icon('i/item', '')));
        }
    }

    question_extend_settings_navigation($quiznode, $PAGE->cm->context)->trim_if_empty();
}

function branchedquiz_grade_item_update($quiz, $grades = null) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    require_once($CFG->dirroot . '/mod/branchedquiz/locallib.php');
    require_once($CFG->libdir . '/gradelib.php');

    if (array_key_exists('cmidnumber', $quiz)) { // May not be always present.
        $params = array('itemname' => $quiz->name, 'idnumber' => $quiz->cmidnumber);
    } else {
        $params = array('itemname' => $quiz->name);
    }

    if ($quiz->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $quiz->grade;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    // What this is trying to do:
    // 1. If the quiz is set to not show grades while the quiz is still open,
    // and is set to show grades after the quiz is closed, then create the
    // grade_item with a show-after date that is the quiz close date.
    // 2. If the quiz is set to not show grades at either of those times,
    // create the grade_item as hidden.
    // 3. If the quiz is set to show grades, create the grade_item visible.
    $openreviewoptions = mod_quiz_display_options::make_from_quiz($quiz,
            mod_quiz_display_options::LATER_WHILE_OPEN);
    $closedreviewoptions = mod_quiz_display_options::make_from_quiz($quiz,
            mod_quiz_display_options::AFTER_CLOSE);
    if ($openreviewoptions->marks < question_display_options::MARK_AND_MAX &&
            $closedreviewoptions->marks < question_display_options::MARK_AND_MAX) {
        $params['hidden'] = 1;

    } else if ($openreviewoptions->marks < question_display_options::MARK_AND_MAX &&
            $closedreviewoptions->marks >= question_display_options::MARK_AND_MAX) {
        if ($quiz->timeclose) {
            $params['hidden'] = $quiz->timeclose;
        } else {
            $params['hidden'] = 1;
        }

    } else {
        // Either
        // a) both open and closed enabled
        // b) open enabled, closed disabled - we can not "hide after",
        // grades are kept visible even after closing.
        $params['hidden'] = 0;
    }

    if (!$params['hidden']) {
        // If the grade item is not hidden by the quiz logic, then we need to
        // hide it if the quiz is hidden from students.
        if (property_exists($quiz, 'visible')) {
            // Saving the quiz form, and cm not yet updated in the database.
            $params['hidden'] = !$quiz->visible;
        } else {
            $cm = get_coursemodule_from_instance('branchedquiz', $quiz->id);
            $params['hidden'] = !$cm->visible;
        }
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    $gradebookgrades = grade_get_grades($quiz->course, 'mod', 'quiz', $quiz->id);
    if (!empty($gradebookgrades->items)) {
        $gradeitem = $gradebookgrades->items[0];
        if ($gradeitem->locked) {
            // NOTE: this is an extremely nasty hack! It is not a bug if this confirmation fails badly. --skodak.
            $confirmregrade = optional_param('confirm_regrade', 0, PARAM_INT);
            if (!$confirmregrade) {
                if (!AJAX_SCRIPT) {
                    $message = get_string('gradeitemislocked', 'grades');
                    $backlink = $CFG->wwwroot . '/mod/branchedquiz/report.php?q=' . $quiz->id .
                            '&amp;mode=overview';
                    $regradelink = qualified_me() . '&amp;confirm_regrade=1';
                    echo $OUTPUT->box_start('generalbox', 'notice');
                    echo '<p>'. $message .'</p>';
                    echo $OUTPUT->container_start('buttons');
                    echo $OUTPUT->single_button($regradelink, get_string('regradeanyway', 'grades'));
                    echo $OUTPUT->single_button($backlink,  get_string('cancel'));
                    echo $OUTPUT->container_end();
                    echo $OUTPUT->box_end();
                }
                return GRADE_UPDATE_ITEM_LOCKED;
            }
        }
    }

    return grade_update('mod/branchedquiz', $quiz->course, 'mod', 'branchedquiz', $quiz->id, 0, $grades, $params);
}

function branchedquiz_update_grades($quiz, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    if ($quiz->grade == 0) {
        branchedquiz_grade_item_update($quiz);

    } else if ($grades = quiz_get_user_grades($quiz, $userid)) {
        branchedquiz_grade_item_update($quiz, $grades);

    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        branchedquiz_grade_item_update($quiz, $grade);

    } else {
        branchedquiz_grade_item_update($quiz);
    }
}

function branchedquiz_attempt_summary_link_to_reports($quiz, $cm, $context, $returnzero = false,
        $currentgroup = 0) {
    global $CFG;
    $summary = quiz_num_attempt_summary($quiz, $cm, $returnzero, $currentgroup);
    if (!$summary) {
        return '';
    }
    require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
    $url = new moodle_url('/mod/branchedquiz/report.php', array(
            'id' => $cm->id, 'mode' => quiz_report_default_report($context)));
    return html_writer::link($url, $summary);
}

function branchedquiz_get_edges($quiz) {
    global $DB;
    $edges = $DB->get_records_sql('SELECT {branchedquiz_edge}.* FROM {branchedquiz_edge} INNER JOIN {quiz_slots} ON {quiz_slots}.id = {branchedquiz_edge}.slotid WHERE {quiz_slots}.quizid = ?', array($quiz->id));

    foreach ($edges as $edge) {
        if ($edge->operator == OPERATOR_LESS_OR_EQUAL && $edge->upperbound == null) {
            $edge->operator = OPERATOR_UI_ONLY_MIN;
        } else if ($edge->operator == OPERATOR_LESS_OR_EQUAL && $edge->lowerbound == null) {
            $edge->operator = OPERATOR_UI_ONLY_MAX;
        } else if ($edge->operator == OPERATOR_LESS && $edge->lowerbound == null) {
            $edge->operator = OPERATOR_UI_ONLY_LESS;
        } else if ($edge->operator == OPERATOR_LESS && $edge->upperbound == null) {
            $edge->operator = OPERATOR_UI_ONLY_MORE;
        }
    }

    return $edges;
}

function branchedquiz_get_node($slot) {
    global $DB;
    return $DB->get_record_sql('SELECT * FROM {branchedquiz_node} WHERE slotid = ?', array($slot));
}