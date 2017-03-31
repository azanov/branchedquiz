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

require_once($CFG->dirroot.'/mod/quiz/renderer.php');
require_once($CFG->dirroot.'/mod/branchedquiz/lib.php');

/**
 * The renderer for the quiz module.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_branchedquiz_renderer extends mod_quiz_renderer {

    /**
     * Branched Attempt Page
     *
     * @param quiz_attempt $attemptobj Instance of quiz_attempt
     * @param int $page Current page number
     * @param quiz_access_manager $accessmanager Instance of quiz_access_manager
     * @param array $messages An array of messages
     * @param array $slots Contains an array of integers that relate to questions
     * @param int $id The ID of an attempt
     * @param int $nextpage The number of the next page
     */
    public function branched_attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id,
                                          $nextpage) {
        $output = '';
        $output .= $this->header();
        $output .= $this->quiz_notices($messages);
        $output .= $this->branched_attempt_form($attemptobj, $page, $slots, $id, $nextpage);
        $output .= $this->footer();
        return $output;
    }
    public function finish_review_link(quiz_attempt $attemptobj) {

        global $CFG;
        $url = $CFG->wwwroot . '/mod/branchedquiz/view.php?id=' . $attemptobj->get_cmid();

        if ($attemptobj->get_access_manager(time())->attempt_must_be_in_popup()) {
            $this->page->requires->js_init_call('M.mod_quiz.secure_window.init_close_button',
                    array($url), false, quiz_get_js_module());
            return html_writer::empty_tag('input', array('type' => 'button',
                    'value' => get_string('finishreview', 'quiz'),
                    'id' => 'secureclosebutton',
                    'class' => 'mod_quiz-next-nav'));

        } else {
            return html_writer::link($url, get_string('finishreview', 'quiz'),
                    array('class' => 'mod_quiz-next-nav'));
        }
    }

    protected function branched_attempt_navigation_buttons($page, $lastpage, $answered, $navmethod = 'free') {
        $output = '';

        $output .= html_writer::start_tag('div', array('class' => 'submitbtns'));

        if ($lastpage) {
            $nextlabel = get_string('endtest', 'quiz');
        } else {
            $nextlabel = get_string('navigatenext', 'quiz');
        }

        // Enable the 'next' button, if the question has been asnwered.
        if ($answered) {
            $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'next',
                'value' => $nextlabel, 'class' => 'mod_quiz-next-nav'));
        }

        return $output;
    }

    public function navigation_panel(quiz_nav_panel_base $panel) {

        $output = '';
        $userpicture = $panel->user_picture();
        if ($userpicture) {
            $fullname = fullname($userpicture->user);
            if ($userpicture->size === true) {
                $fullname = html_writer::div($fullname);
            }
            $output .= html_writer::tag('div', $this->render($userpicture) . $fullname,
                    array('id' => 'user-picture', 'class' => 'clearfix'));
        }
        $output .= $panel->render_before_button_bits($this);

        $output .= html_writer::tag('div', $panel->render_end_bits($this),
                array('class' => 'othernav'));

        $this->page->requires->js_init_call('M.mod_quiz.nav.init', null, false,
                quiz_get_js_module());

        return $output;
    }

    /**
     * Ouputs the form for making an attempt
     *
     * @param quiz_attempt $attemptobj
     * @param int $page Current page number
     * @param array $slots Array of integers relating to questions
     * @param int $id ID of the attempt
     * @param int $nextpage Next page number
     */
    public function branched_attempt_form($attemptobj, $page, $slots, $id, $nextpage) {

        $slot = end($slots);
        $points = $attemptobj->get_unformatted_question_mark($slot);

        $answered = false;

        // Check if question is answered or is not a real question (descirption question).
        if (!is_null($points) || !$attemptobj->is_real_question($slot)) {
            $answered = true;
        }

        $output = '';

        // Start the form.
        $output .= html_writer::start_tag('form',
            array('action' => $attemptobj->processattempt_url(), 'method' => 'post',
                'enctype' => 'multipart/form-data', 'accept-charset' => 'utf-8',
                'id' => 'responseform'));
        $output .= html_writer::start_tag('div');

        // Print all the questions.
        foreach ($slots as $slot) {
            $output .= $attemptobj->render_question($slot, false, $this,
                $attemptobj->attempt_url($slot, $page), $this);
        }

        $navmethod = $attemptobj->get_quiz()->navmethod;
        // Replaced $attemptobj->is_last_page($page) to false - to hide "finish attempt".
        $output .= $this->branched_attempt_navigation_buttons($page, false, $answered, $navmethod);

        // Some hidden fields to trach what is going on.
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'attempt',
            'value' => $attemptobj->get_attemptid()));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'thispage',
            'value' => $page, 'id' => 'followingpage'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'nextpage',
            'value' => $nextpage));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'timeup',
            'value' => '0', 'id' => 'timeup'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey',
            'value' => sesskey()));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'scrollpos',
            'value' => '', 'id' => 'scrollpos'));

        // Add a hidden field with questionids. Do this at the end of the form, so
        // if you navigate before the form has finished loading, it does not wipe all
        // the student's answers.
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'slots',
            'value' => implode(',', $attemptobj->get_active_slots($page))));

        // Finish the form.
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        $output .= $this->connection_warning();

        return $output;
    }

    /**
     * Generates the table of summarydata
     *
     * @param quiz_attempt $attemptobj
     * @param mod_quiz_display_options $displayoptions
     */
    public function summary_table($attemptobj, $displayoptions) {
        // Prepare the summary table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable quizsummaryofattempt boxaligncenter';
        $table->head = array(get_string('question', 'quiz'), get_string('status', 'quiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $markscolumn = $displayoptions->marks >= question_display_options::MARK_AND_MAX;
        if ($markscolumn) {
            $table->head[] = get_string('marks', 'quiz');
            $table->align[] = 'left';
            $table->size[] = '';
        }
        $tablewidth = count($table->align);
        $table->data = array();

        // Get the summary info for each question.
        // Show only questions, which the user has seen in current path.
        $slots = get_current_path_slots($attemptobj);

        $page = 0;
        $subpage = 0;

        foreach ($slots as $slot) {

            $slotid = page_to_slotid($attemptobj->get_quizobj(), $slot);
            $node = branchedquiz_get_node($slotid);

            if ($node->nodetype == 0) {
                $page++;
                $subpage = 0;
            } else {
                $subpage++;
            }

            // Add a section headings if we need one here.
            $heading = $attemptobj->get_heading_before_slot($slot);
            if ($heading) {
                $cell = new html_table_cell(format_string($heading));
                $cell->header = true;
                $cell->colspan = $tablewidth;
                $table->data[] = array($cell);
                $table->rowclasses[] = 'quizsummaryheading';
            }

            // Don't display information items.
            if (!$attemptobj->is_real_question($slot)) {
                continue;
            }

            // Real question, show it.
            $flag = '';
            if ($attemptobj->is_question_flagged($slot)) {
                $flag = html_writer::empty_tag('img', array('src' => $this->pix_url('i/flagged'),
                    'alt' => get_string('flagged', 'question'), 'class' => 'questionflag icon-post'));
            }
            if ($attemptobj->can_navigate_to($slot)) {
                $row = array(html_writer::link($attemptobj->attempt_url($slot),
                    $page . ($node->nodetype == 1 ? '.'.$subpage : '') . $flag),
                    $attemptobj->get_question_status($slot, $displayoptions->correctness));
            } else {
                $row = array($attemptobj->get_question_number($slot) . $flag,
                    $attemptobj->get_question_status($slot, $displayoptions->correctness));
            }
            if ($markscolumn) {
                $row[] = $attemptobj->get_question_mark($slot);
            }
            $table->data[] = $row;

            $table->rowclasses[] = 'quizsummary' . $slot . ' ' . $attemptobj->get_question_state_class(
                    $slot, false) . ($node->nodetype == 0 ? ' main-question' : ' sub-question');
        }

        // Print the summary table.
        $output = html_writer::table($table);

        return $output;
    }

    public function questions(quiz_attempt $attemptobj, $reviewing, $slots, $page, $showall,
                              mod_quiz_display_options $displayoptions) {
        $output = '';
        $page = 0;
        $subpage = 0;

        foreach ($slots as $slot) {

            $slotid = page_to_slotid($attemptobj->get_quizobj(), $slot);
            $node = branchedquiz_get_node($slotid);

            if ($node->nodetype == 0) {
                $page++;
                $subpage = 0;
            } else {
                $subpage++;
            }

            $output .= $attemptobj->render_question_ex($slot, $reviewing, $this,
                    $attemptobj->review_url($slot, $page, $showall), $page, $subpage);
        }
        return $output;
    }

}
