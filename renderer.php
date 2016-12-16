<?php
defined('MOODLE_INTERNAL') || die();


/**
 * Renderer for outputting parts of a question when the actual behaviour
 * used is not available.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_missing_renderer extends qbehaviour_renderer {
    public function controls(question_attempt $qa, question_display_options $options) {
        return html_writer::tag('div',
                get_string('questionusedunknownmodel', 'qbehaviour_missing'),
                array('class' => 'warning'));
    }
}