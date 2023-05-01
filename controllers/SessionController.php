<?php
namespace Controllers;

use Exception;
use Models\MaQuestion;
use Models\Session;
use Models\SessionQuestion;
use Models\TfQuestion;
use PDO;
use PDOException;

require_once(__DIR__ . '/../models/Session.php');
require_once(__DIR__ . '/../models/SessionQuestion.php');

/**
 * Class SessionController
 * @package Controllers
 */
class SessionController {
    /**
     * @var PDO The database connection object
     */
    private $db;

    /**
     * SessionController constructor.
     * @param PDO $db The database connection object
     */
    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Creates a new session for the specified user.
     *
     * @param string $username The username of the user for whom to create a session
     * @return int The ID of the newly created session
     * @throws Exception If an error occurs while creating the session
     */
    public function createSession(string $username): int {
        try {
            // Create a new session object
            $session = new Session($username, 0, 0, $this->db);

            // Insert the session into the database
            $query = "INSERT INTO sessions (username, max_points, actual_points) VALUES (:username, :max_points, :actual_points)";
            $stmt = $this->db->prepare($query);
            $username = $session->getUsername();
            $maxPoints = $session->getMaxPoints();
            $actualPoints = $session->getActualPoints();
            $stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $stmt->bindParam(":max_points", $maxPoints, PDO::PARAM_INT);
            $stmt->bindParam(":actual_points", $actualPoints, PDO::PARAM_INT);
            $stmt->execute();
            $sessionId = (int) $this->db->lastInsertId();

            // Set the ID of the session object
            $session->setId($sessionId);

            // Add multiple-choice questions to the session
            $session->addQuestions("MA", MaQuestion::getRandomQuestions($this->db, 2));

            // Add true/false questions to the session
            $session->addQuestions("TF", TfQuestion::getRandomQuestions($this->db, 2));

            return $sessionId;
        } catch (Exception $e) {
            // Log the error and re-throw the exception
            error_log($e->getMessage());
            throw new Exception('Error creating session');
        }
    }

    /**
     * Updates the actual points for the specified session.
     *
     * @param int $sessionId The ID of the session to update
     * @param int $points The new actual points value for the session
     * @throws Exception If an error occurs while updating the session points
     */
    public function updateSessionPoints(int $sessionId, int $points): void {
        try {
            $query = "UPDATE sessions SET actual_points = :actual_points WHERE id = :session_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":actual_points", $points, PDO::PARAM_INT);
            $stmt->bindParam(":session_id", $sessionId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            // Log the error and re-throw the exception
            error_log($e->getMessage());
            throw new Exception('Error updating session points');
        }
    }

    /**
     * Retrieve questions of a session by session ID.
     *
     * @param int $sessionId The ID of the session.
     * @return array An array of Question objects.
     * @throws Exception If an unknown question type is encountered.
     */
    public function getSessionQuestions(int $sessionId): array
    {
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

    /**
     * Returns an array with the status of a session
     *
     * @param int $sessionId The ID of the session to get the status of
     * @return array An array with the status of the session
     * @throws Exception If the session is not found
     */
    public function getSessionStatus(int $sessionId): array {
        try {
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
        } catch (PDOException $e) {
            throw new Exception("Error getting session status: " . $e->getMessage());
        }
    }
}