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
 * Renderer outputting the quiz editing UI.
 *
 * @package mod_quiz
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_branchedquiz\output;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/quiz/classes/output/edit_renderer.php');

use \mod_quiz\structure;
use \html_writer;

/**
 * Renderer outputting the quiz editing UI.
 *
 * @copyright 2013 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.7
 */
class bqedit_renderer extends \mod_quiz\output\edit_renderer {

    public function edit_page(\quiz $quizobj, structure $structure,
            \question_edit_contexts $contexts, \moodle_url $pageurl, array $pagevars) {
        $output = '';

        // Page title.
        $output .= $this->heading_with_help(get_string('editingquizx', 'quiz',
                format_string($quizobj->get_quiz_name())), 'editingquiz', 'quiz', '',
                get_string('basicideasofquiz', 'quiz'), 2);

        // Information at the top.
        $output .= $this->branchedquiz_state_warnings($quizobj);
        $output .= $this->quiz_information($structure);
        $output .= $this->maximum_grade_input($structure, $pageurl);
        $output .= $this->repaginate_button($structure, $pageurl);
        $output .= $this->total_marks($quizobj->get_quiz());

        $output .= '<script src="https://jsplumbtoolkit.com/community/demo/statemachine/lib/jsPlumb-2.2.8.js"></script>';
        $output .= '<style>@import url("/mod/branchedquiz/styles.css");</style>';

        // Show the questions organised into sections and pages.
        $output .= $this->start_section_list($structure);

        foreach ($structure->get_sections() as $section) {
            $output .= $this->start_section($structure, $section);
            $output .= $this->questions_in_section($structure, $section, $contexts, $pagevars, $pageurl);

            if ($structure->is_last_section($section)) {
                $output .= \html_writer::start_div('last-add-menu');
                $output .= html_writer::tag('span', $this->add_menu_actions($structure, 0,
                        $pageurl, $contexts, $pagevars), array('class' => 'add-menu-outer'));
                $output .= \html_writer::end_div();
            }

            $output .= $this->end_section();
        }

        $output .= $this->end_section_list();

        // Initialise the JavaScript.
        $this->initialise_editing_javascript($structure, $contexts, $pagevars, $pageurl);

        // Include the contents of any other popups required.
        if ($structure->can_be_edited()) {
            $popups = '';

            $popups .= $this->question_bank_loading();
            $this->page->requires->yui_module('moodle-mod_quiz-quizquestionbank',
                    'M.mod_quiz.quizquestionbank.init',
                    array('class' => 'questionbank', 'cmid' => $structure->get_cmid()));

            $popups .= $this->random_question_form($pageurl, $contexts, $pagevars);
            $this->page->requires->yui_module('moodle-mod_quiz-randomquestion',
                    'M.mod_quiz.randomquestion.init');

            $output .= html_writer::div($popups, 'mod_quiz_edit_forms');

            // Include the question chooser.
            $output .= $this->question_chooser();
            $this->page->requires->yui_module('moodle-mod_quiz-questionchooser', 'M.mod_quiz.init_questionchooser');
        }

        $output .= '<script src="/mod/branchedquiz/edit.js"></script>';

        return $output;
    }

    public function questions_in_section(structure $structure, $section,
            $contexts, $pagevars, $pageurl) {

        $output = '';
        foreach ($structure->get_slots_in_section($section->id) as $slot) {
            $output .= $this->question_row($structure, $slot, $contexts, $pagevars, $pageurl);
        }
        return html_writer::tag('ul', $output, array('class' => 'section img-text js-question-canvas jtk-surface jtk-surface-nopan', 'id' => 'questionCanvas'));
    }

    public function question_row(structure $structure, $slot, $contexts, $pagevars, $pageurl) {
        $output = '';

        $output .= $this->page_row($structure, $slot, $contexts, $pagevars, $pageurl);

        // Page split/join icon.
        $joinhtml = '';
        if ($structure->can_be_edited() && !$structure->is_last_slot_in_quiz($slot) &&
                                            !$structure->is_last_slot_in_section($slot)) {
            $joinhtml = $this->page_split_join_button($structure, $slot);
        }
        // Question HTML.
        $questionhtml = $this->question($structure, $slot, $pageurl);
        $qtype = $structure->get_question_type_for_slot($slot);
        $questionclasses = 'activity ' . $qtype . ' qtype_' . $qtype . ' slot';

        $questionhtml = '<div class="ep"></div>'.$questionhtml;

        $question = $structure->get_question_in_slot($slot);

        $output .= html_writer::tag('li', $questionhtml . $joinhtml,
                array('class' => $questionclasses, 'id' => 'slot-' . $structure->get_slot_id_for_slot($slot),
                        'data-canfinish' => $structure->can_finish_during_the_attempt($slot),
                        'data-question-id' => $question->questionid,
                        'data-slot' => $slot
                        ));

        return $output;
    }

    protected function start_section($structure, $section) {

        $output = '';

        $sectionstyle = '';
        if ($structure->is_only_one_slot_in_section($section)) {
            $sectionstyle = ' only-has-one-slot';
        }

        $output .= html_writer::start_tag('li', array('id' => 'section-'.$section->id,
            'class' => 'section main clearfix'.$sectionstyle, 'role' => 'region',
            'aria-label' => $section->heading));

        $output .= html_writer::start_div('content');

        // $output .= html_writer::start_div('section-heading');

        // $headingtext = $this->heading(html_writer::span(
        //         html_writer::span($section->heading, 'instancesection'), 'sectioninstance'), 3);

        // if (!$structure->can_be_edited()) {
        //     $editsectionheadingicon = '';
        // } else {
        //     $editsectionheadingicon = html_writer::link(new \moodle_url('#'),
        //         $this->pix_icon('t/editstring', get_string('sectionheadingedit', 'quiz', $section->heading),
        //                 'moodle', array('class' => 'editicon visibleifjs')),
        //                 array('class' => 'editing_section', 'data-action' => 'edit_section_title'));
        // }
        // $output .= html_writer::div($headingtext . $editsectionheadingicon, 'instancesectioncontainer');

        // if (!$structure->is_first_section($section) && $structure->can_be_edited()) {
        //     $output .= $this->section_remove_icon($section);
        // }
        // $output .= $this->section_shuffle_questions($structure, $section);

        // $output .= html_writer::end_div($output, 'section-heading');

        return $output;
    }

	protected function initialise_editing_javascript(structure $structure, \question_edit_contexts $contexts, array $pagevars, \moodle_url $pageurl) {

        $config = new \stdClass();
        $config->resourceurl = '/mod/branchedquiz/edit_rest.php';
        $config->sectionurl = '/mod/branchedquiz/edit_rest.php';
        $config->pageparams = array();
        $config->questiondecimalpoints = $structure->get_decimal_places_for_question_marks();
        $config->pagehtml = $this->new_page_template($structure, $contexts, $pagevars, $pageurl);
        $config->addpageiconhtml = $this->add_page_icon_template($structure);

        // $this->page->requires->yui_module('moodle-mod_quiz-toolboxes',
        //         'M.mod_quiz.init_resource_toolbox',
        //         array(array(
        //                 'courseid' => $structure->get_courseid(),
        //                 'quizid' => $structure->get_quizid(),
        //                 'ajaxurl' => $config->resourceurl,
        //                 'config' => $config,
        //         ))
        // );
        unset($config->pagehtml);
        unset($config->addpageiconhtml);

        // $this->page->requires->yui_module('moodle-mod_quiz-toolboxes',
        //         'M.mod_quiz.init_section_toolbox',
        //         array(array(
        //                 'courseid' => $structure,
        //                 'quizid' => $structure->get_quizid(),
        //                 'ajaxurl' => $config->sectionurl,
        //                 'config' => $config,
        //         ))
        // );

        // $this->page->requires->yui_module('moodle-mod_quiz-dragdrop', 'M.mod_quiz.init_section_dragdrop',
        //         array(array(
        //                 'courseid' => $structure,
        //                 'quizid' => $structure->get_quizid(),
        //                 'ajaxurl' => $config->sectionurl,
        //                 'config' => $config,
        //         )), null, true);

        // $this->page->requires->yui_module('moodle-mod_quiz-dragdrop', 'M.mod_quiz.init_resource_dragdrop',
        //         array(array(
        //                 'courseid' => $structure,
        //                 'quizid' => $structure->get_quizid(),
        //                 'ajaxurl' => $config->resourceurl,
        //                 'config' => $config,
        //         )), null, true);

        // Require various strings for the command toolbox.
        $this->page->requires->strings_for_js(array(
                'clicktohideshow',
                'deletechecktype',
                'deletechecktypename',
                'edittitle',
                'edittitleinstructions',
                'emptydragdropregion',
                'hide',
                'markedthistopic',
                'markthistopic',
                'move',
                'movecontent',
                'moveleft',
                'movesection',
                'page',
                'question',
                'selectall',
                'show',
                'tocontent',
        ), 'moodle');

        $this->page->requires->strings_for_js(array(
                'addpagebreak',
                'confirmremovesectionheading',
                'confirmremovequestion',
                'dragtoafter',
                'dragtostart',
                'numquestionsx',
                'sectionheadingedit',
                'sectionheadingremove',
                'removepagebreak',
                'questiondependencyadd',
                'questiondependencyfree',
                'questiondependencyremove',
                'questiondependsonprevious',
        ), 'quiz');

        foreach (\question_bank::get_all_qtypes() as $qtype => $notused) {
            $this->page->requires->string_for_js('pluginname', 'qtype_' . $qtype);
        }

        return true;
    }

    public function section_remove_icon($section) {
        $title = get_string('sectionheadingremove', 'quiz', $section->heading);
        $url = new \moodle_url('/mod/branchedquiz/edit.php',
                array('sesskey' => sesskey(), 'removesection' => '1', 'sectionid' => $section->id));
        $image = $this->pix_icon('t/delete', $title);
        return $this->action_link($url, $image, null, array(
                'class' => 'cm-edit-action editing_delete', 'data-action' => 'deletesection'));
    }

    public function edit_menu_actions(structure $structure, $page, \moodle_url $pageurl, array $pagevars) {
        $questioncategoryid = question_get_category_id_from_pagevars($pagevars);
        static $str;
        if (!isset($str)) {
            $str = get_strings(array('addasection', 'addaquestion', 'addarandomquestion',
                    'addarandomselectedquestion', 'questionbank'), 'quiz');
        }

        // Get section, page, slotnumber and maxmark.
        $actions = array();

        // Add a new section to the add_menu if possible. This is always added to the HTML
        // then hidden with CSS when no needed, so that as things are re-ordered, etc. with
        // Ajax it can be relevaled again when necessary.
        $params = array('cmid' => $structure->get_cmid(), 'addsectionatpage' => $page);

        $actions['addasection'] = new \action_menu_link_secondary(
                new \moodle_url($pageurl, $params),
                new \pix_icon('t/add', $str->addasection, 'moodle', array('class' => 'iconsmall', 'title' => '')),
                $str->addasection, array('class' => 'cm-edit-action addasection', 'data-action' => 'addasection')
        );

        // Add a new question to the quiz.
        $returnurl = new \moodle_url($pageurl, array('addonpage' => $page));
        $params = array('returnurl' => $returnurl->out_as_local_url(false),
                'cmid' => $structure->get_cmid(), 'category' => $questioncategoryid,
                'addonpage' => $page, 'appendqnumstring' => 'addquestion');

        $actions['addaquestion'] = new \action_menu_link_secondary(
            new \moodle_url('/question/addquestion.php', $params),
            new \pix_icon('t/add', $str->addaquestion, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $str->addaquestion, array('class' => 'cm-edit-action addquestion', 'data-action' => 'addquestion')
        );

        // Call question bank.
        $icon = new \pix_icon('t/add', $str->questionbank, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        if ($page) {
            $title = get_string('addquestionfrombanktopage', 'quiz', $page);
        } else {
            $title = get_string('addquestionfrombankatend', 'quiz');
        }
        $attributes = array('class' => 'cm-edit-action questionbank',
                'data-header' => $title, 'data-action' => 'questionbank', 'data-addonpage' => $page);
        $actions['questionbank'] = new \action_menu_link_secondary($pageurl, $icon, $str->questionbank, $attributes);

        // Add a random question.
        $returnurl = new \moodle_url('/mod/branchedquiz/edit.php', array('cmid' => $structure->get_cmid(), 'data-addonpage' => $page));
        $params = array('returnurl' => $returnurl, 'cmid' => $structure->get_cmid(), 'appendqnumstring' => 'addarandomquestion');
        $url = new \moodle_url('/mod/branchedquiz/addrandom.php', $params);
        $icon = new \pix_icon('t/add', $str->addarandomquestion, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        $attributes = array('class' => 'cm-edit-action addarandomquestion', 'data-action' => 'addarandomquestion');
        if ($page) {
            $title = get_string('addrandomquestiontopage', 'quiz', $page);
        } else {
            $title = get_string('addrandomquestionatend', 'quiz');
        }
        $attributes = array_merge(array('data-header' => $title, 'data-addonpage' => $page), $attributes);
        $actions['addarandomquestion'] = new \action_menu_link_secondary($url, $icon, $str->addarandomquestion, $attributes);

        return $actions;
    }

    protected function random_question_form(\moodle_url $thispageurl, \question_edit_contexts $contexts, array $pagevars) {

        if (!$contexts->have_cap('moodle/question:useall')) {
            return '';
        }
        $randomform = new \quiz_add_random_form(new \moodle_url('/mod/branchedquiz/addrandom.php'),
                                 array('contexts' => $contexts, 'cat' => $pagevars['cat']));
        $randomform->set_data(array(
                'category' => $pagevars['cat'],
                'returnurl' => $thispageurl->out_as_local_url(true),
                'randomnumber' => 1,
                'cmid' => $thispageurl->param('cmid'),
        ));
        return html_writer::div($randomform->render(), 'randomquestionformforpopup');
    }

    public function branchedquiz_state_warnings(\quiz $quizobj) {
        $warnings = array();

        if (quiz_has_attempts($quizobj->get_quizid())) {
            $reviewlink = branchedquiz_attempt_summary_link_to_reports($quizobj->get_quiz(),
                    $quizobj->get_cm(), $quizobj->get_context());
            $warnings[] = get_string('cannoteditafterattempts', 'quiz', $reviewlink);
        }

        if (empty($warnings)) {
            return '';
        }

        $output = array();
        foreach ($warnings as $warning) {
            $output[] = \html_writer::tag('p', $warning);
        }
        return $this->box(implode("\n", $output), 'statusdisplay');
    }

    public function question(structure $structure, $slot, \moodle_url $pageurl) {
        $output = '';
        // $output .= html_writer::start_tag('div');

        if ($structure->can_be_edited()) {
            $output .= $this->question_move_icon($structure, $slot);
        }

        // $output .= html_writer::start_div('mod-indent-outer');
        // $output .= $this->question_number($structure->get_displayed_number_for_slot($slot));

        // This div is used to indent the content.
        // $output .= html_writer::div('', 'mod-indent');

        // Display the link to the question (or do nothing if question has no url).
        if ($structure->get_question_type_for_slot($slot) == 'random') {
            $questionname = $this->random_question($structure, $slot, $pageurl);
        } else {
            $questionname = $this->question_name($structure, $slot, $pageurl);
        }

        // Start the div for the activity title, excluding the edit icons.
        // $output .= html_writer::start_div('activityinstance');
        $output .= $questionname;

        // Closing the tag which contains everything but edit icons. Content part of the module should not be part of this.
        // $output .= html_writer::end_tag('div'); // .activityinstance.

        // Action icons.
        // $questionicons = '';
        // $questionicons .= $this->question_preview_icon($structure->get_quiz(), $structure->get_question_in_slot($slot));
        // if ($structure->can_be_edited()) {
        //     $questionicons .= $this->question_remove_icon($structure, $slot, $pageurl);
        // }
        // $questionicons .= $this->marked_out_of_field($structure, $slot);
        // $output .= html_writer::span($questionicons, 'actions'); // Required to add js spinner icon.
        // if ($structure->can_be_edited()) {
        //     $output .= $this->question_dependency_icon($structure, $slot);
        // }

        // End of indentation div.
        // $output .= html_writer::end_tag('div');
        // $output .= html_writer::end_tag('div');

        return $output;
    }

    public function question_name(structure $structure, $slot, $pageurl) {
        $output = '';

        $question = $structure->get_question_in_slot($slot);
        $editurl = new \moodle_url('/question/question.php', array(
                'returnurl' => $pageurl->out_as_local_url(),
                'cmid' => $structure->get_cmid(), 'id' => $question->id));

        $instancename = quiz_question_tostring($question);

        $qtype = \question_bank::get_qtype($question->qtype, false);
        $namestr = $qtype->local_name();

        $icon = $this->pix_icon('icon', $namestr, $qtype->plugin_name(), array('title' => $namestr,
                'class' => 'icon activityicon', 'alt' => ' ', 'role' => 'presentation'));

        // $editicon = $this->pix_icon('t/edit', '', 'moodle', array('title' => ''));

        // Need plain question name without html tags for link title.
        $title = shorten_text(format_string($question->name), 100);

        // Display the link itself.
        $activitylink = $icon . html_writer::tag('span', $instancename, array('class' => 'instancename'));
        $output .= html_writer::link($editurl, $activitylink,
                array('title' => get_string('editquestion', 'quiz').' '.$title));

        return $output;
    }

    public function random_question(structure $structure, $slot, $pageurl) {

        $question = $structure->get_question_in_slot($slot);
        $editurl = new \moodle_url('/question/question.php', array(
                'returnurl' => $pageurl->out_as_local_url(),
                'cmid' => $structure->get_cmid(), 'id' => $question->id));

        $temp = clone($question);
        $temp->questiontext = '';
        $instancename = quiz_question_tostring($temp);

        $configuretitle = get_string('configurerandomquestion', 'quiz');
        $qtype = \question_bank::get_qtype($question->qtype, false);
        $namestr = $qtype->local_name();
        $icon = $this->pix_icon('icon', $namestr, $qtype->plugin_name(), array('title' => $namestr,
                'class' => 'icon activityicon', 'alt' => ' ', 'role' => 'presentation'));

        // $editicon = $this->pix_icon('t/edit', $configuretitle, 'moodle', array('title' => ''));

        // If this is a random question, display a link to show the questions
        // selected from in the question bank.
        $qbankurl = new \moodle_url('/question/edit.php', array(
                'cmid' => $structure->get_cmid(),
                'cat' => $question->category . ',' . $question->contextid,
                'recurse' => !empty($question->questiontext)));
        $qbanklink = ' ' . \html_writer::link($qbankurl,
                get_string('seequestions', 'quiz'), array('class' => 'mod_quiz_random_qbank_link'));

        return html_writer::link($editurl, $icon, array('title' => $configuretitle)) .
                ' ' . $instancename . ' ' . $qbanklink;
    }

    public function page_row(structure $structure, $slot, $contexts, $pagevars, $pageurl) {
        return '';
    }

    protected function repaginate_button(structure $structure, \moodle_url $pageurl) {
        return '';
    }

    public function maximum_grade_input($structure, \moodle_url $pageurl) {
        return '';
    }

    protected function repaginate_form(structure $structure, \moodle_url $pageurl) {
        return '';
    }

    public function marked_out_of_field(structure $structure, $slot) {
        return '';
    }

    public function total_marks($quiz) {
        return '';
    }

    public function section_shuffle_questions(structure $structure, $section) {
        return '';
    }

    public function question_move_icon(structure $structure, $slot) {
        return '';
    }

    public function question_number($number) {
        return '';
    }

    public function question_preview_icon($quiz, $question, $label = null, $variant = null) {
        return '';
    }

    public function page_split_join_button($structure, $slot) {
        return '';
    }

    public function question_dependency_icon($structure, $slot) {
        return '';
    }
}
