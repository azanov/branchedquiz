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
 * Defines the renderer for the quiz module.
 *
 * @package   mod_quiz
 * @copyright 2011 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/quiz/renderer.php');

/**
 * The renderer for the quiz module.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_branchedquiz_renderer extends mod_quiz_renderer {
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

    protected function attempt_navigation_buttons($page, $lastpage) {
        $output = '';

        $output .= html_writer::start_tag('div', array('class' => 'submitbtns'));

        // Hide the button to return to the previous question

        // if ($page > 0) {
        //     $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'previous',
        //             'value' => get_string('navigateprevious', 'quiz'), 'class' => 'mod_quiz-prev-nav'));
        // }
        if ($lastpage) {
            $nextlabel = get_string('endtest', 'quiz');
        } else {
            $nextlabel = get_string('navigatenext', 'quiz');
        }
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'next',
                'value' => $nextlabel, 'class' => 'mod_quiz-next-nav'));
        $output .= html_writer::end_tag('div');

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

        // $bcc = $panel->get_button_container_class();
        // $output .= html_writer::start_tag('div', array('class' => "qn_buttons clearfix $bcc"));
        // foreach ($panel->get_question_buttons() as $button) {
        //     $output .= $this->render($button);
        // }
        // $output .= html_writer::end_tag('div');

        $output .= html_writer::tag('div', $panel->render_end_bits($this),
                array('class' => 'othernav'));

        $this->page->requires->js_init_call('M.mod_quiz.nav.init', null, false,
                quiz_get_js_module());

        return $output;
    }
}
