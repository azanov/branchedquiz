<?php

defined('MOODLE_INTERNAL') || die();

class qbehaviour_testflow extends question_behaviour {

    // public function __construct(question_attempt $qa, $preferredbehaviour) {
    //     parent::__construct($qa, $preferredbehaviour);
    //     $this->preferredbehaviour = $preferredbehaviour;
    // }

    public function is_compatible_question(question_definition $question) {
        return true;
    }

    public function is_archetypal() {
        return true;
    }

}