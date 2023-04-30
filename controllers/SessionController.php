<?php
namespace Controllers;

use Exception;
use Models\MaQuestion;
use Models\Session;
use Models\SessionQuestion;
use Models\TfQuestion;
use PDO;

require_once(__DIR__ . '/../models/Session.php');
require_once(__DIR__ . '/../models/SessionQuestion.php');

class SessionController {
    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    function createSession($username) {
        $session = new Session($username, 0, 0, $this->db);
        $query = "INSERT INTO sessions (username, max_points, actual_points) VALUES (:username, :max_points, :actual_points)";
        $stmt = $this->db->prepare($query);
        $username = $session->getUsername();
        $maxPoints = $session->getMaxPoints();
        $actualPoints = $session->getActualPoints();
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":max_points", $maxPoints, PDO::PARAM_INT);
        $stmt->bindParam(":actual_points", $actualPoints, PDO::PARAM_INT);
        $stmt->execute();
        $session->setId($this->db->lastInsertId());

        $session->addQuestions("MA", MaQuestion::getRandomQuestions($this->db, 2)); // Add multiple-choice questions to the session
        $session->addQuestions("TF", TfQuestion::getRandomQuestions($this->db, 2)); // Add true/false questions to the session

        return $session->getId();
    }

    function updateSessionPoints($sessionId, $points) {
        $query = "UPDATE sessions SET actual_points = :actual_points WHERE id = :session_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":actual_points", $points, PDO::PARAM_INT);
        $stmt->bindParam(":session_id", $sessionId, PDO::PARAM_INT);
        $stmt->execute();
    }

    function getSessionQuestions($sessionId) {
        $query = "SELECT * FROM session_questions WHERE sessionid = :session_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $sessionId, PDO::PARAM_INT);
        $stmt->execute();
        $sessionQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $questions = array();
        foreach ($sessionQuestions as $sessionQuestion) {
            $questionType = $sessionQuestion['question_type'];
            $questionId = $sessionQuestion['question_id'];
            switch (strtolower($questionType)) {
                case 'ma':
                    $question = MaQuestion::getById($this->db, $questionId);
                    break;
                case 'tf':
                    $question = TfQuestion::getById($this->db, $questionId);
                    break;
                default:
                    throw new Exception('Unknown question type: ' . $questionType);
            }
            $questions[] = $question;
        }

        return $questions;
    }

    function getSessionStatus($sessionId) {
        $query = "SELECT * FROM sessions WHERE id = :session_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":session_id", $sessionId, PDO::PARAM_INT);
        $stmt->execute();
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            throw new Exception('Session not found');
        }

        $status = array(
            'username' => $session['username'],
            'maxPoints' => $session['max_points'],
            'actualPoints' => $session['actual_points'],
            'questions' => $this->getSessionQuestions($sessionId)
        );

        return $status;
    }
}