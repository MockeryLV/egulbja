<?php

namespace Models;

use PDO;

class Session {
    private $id;
    private $username;
    private $maxPoints;
    private $actualPoints;
    private $db;

    function __construct($username, $maxPoints, $actualPoints, $db, $id = null) {
        $this->id = $id;
        $this->username = $username;
        $this->maxPoints = $maxPoints;
        $this->actualPoints = $actualPoints;
        $this->db = $db;
    }

    function getId() {
        return $this->id;
    }

    function getUsername() {
        return $this->username;
    }

    function getMaxPoints() {
        return $this->maxPoints;
    }

    function getActualPoints() {
        return $this->actualPoints;
    }

    function setId($id): void {
        $this->id = $id;
    }

    public function addQuestions($questionType, $questions) {
        $sessionQuestions = array();
        foreach ($questions as $question) {
            $sessionQuestions[] = new SessionQuestion($question->getId(), $questionType, $question->getId());
        }
        $query = "INSERT INTO session_questions (sessionid, question_type, question_id) VALUES (:session_id, :question_type, :question_id)";
        $stmt = $this->db->prepare($query);
        foreach ($sessionQuestions as $sessionQuestion) {
            $sessionId = $this->getId();
            $questionType = $sessionQuestion->getQuestionType();
            $questionId = $sessionQuestion->getQuestionId();
            $stmt->bindParam(":session_id", $sessionId, PDO::PARAM_INT);
            $stmt->bindParam(":question_type", $questionType, PDO::PARAM_STR);
            $stmt->bindParam(":question_id", $questionId, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}