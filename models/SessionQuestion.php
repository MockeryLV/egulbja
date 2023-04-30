<?php

namespace Models;

class SessionQuestion {
    private $sessionId;
    private $questionType;
    private $questionId;

    function __construct($sessionId, $questionType, $questionId) {
        $this->sessionId = $sessionId;
        $this->questionType = $questionType;
        $this->questionId = $questionId;
    }

    function getSessionId() {
        return $this->sessionId;
    }

    function getQuestionType() {
        return $this->questionType;
    }

    function getQuestionId() {
        return $this->questionId;
    }
}