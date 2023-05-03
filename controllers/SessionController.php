<?php

namespace Controllers;

use Exception;
use Models\MaQuestion;
use Models\Session;
use Models\TfQuestion;
use PDO;

class SessionController {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

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
			$maQuestions = MaQuestion::getRandomQuestions($this->db, 2);
			$this->addQuestionsToSession($sessionId, 'ma', $maQuestions);

			// Add true/false questions to the session
			$tfQuestions = TfQuestion::getRandomQuestions($this->db, 2);
			$this->addQuestionsToSession($sessionId, 'tf', $tfQuestions);

			$questionsCount = $this->getSessionQuestionsCount($sessionId);

			$stmt = $this->db->prepare("UPDATE sessions SET max_points = :max_points WHERE id = :id");
			$stmt->execute([
				'id' => $sessionId,
				'max_points' => $questionsCount
			]);

			return $sessionId;
		} catch (Exception $e) {
			// Log the error and re-throw the exception
			error_log($e->getMessage());
			throw new Exception('Error creating session');
		}
	}

	public function getQuestionsBySessionId(int $sessionId): array
	{
		$query = "SELECT sq.*, IFNULL(mq.text, tf.text) AS question_text 
              FROM session_questions sq 
              LEFT JOIN maquestions mq ON mq.id = sq.maquestion_id AND sq.question_type = 'maquestion'
              LEFT JOIN tfquestions tf ON tf.id = sq.tfquestion_id AND sq.question_type = 'tfquestion'
              WHERE sq.sessionid = :sessionId";
		$stmt = $this->db->prepare($query);
		$stmt->execute(['sessionId' => $sessionId]);

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getAnswerBySessionIdAndQuestionId($sessionId, $questionId)
	{
		$stmt = $this->db->prepare('SELECT a.*
                                FROM session_answers a
                                JOIN session_questions q ON a.session_question_id = q.id
                                WHERE a.session_id = :sessionId
                                AND (q.question_type = "maquestion" AND a.maquestion_variant_id = :questionId)
                                OR (q.question_type = "tfquestion" AND q.tfquestion_id = :questionId)');
		$stmt->bindParam(':sessionId', $sessionId);
		$stmt->bindParam(':questionId', $questionId);
		$stmt->execute();

		return $stmt->fetch(PDO::FETCH_ASSOC);
	}


	public function getVariantsByQuestionId($questionId)
	{
		$stmt = $this->db->prepare('SELECT * FROM maquestion_variants WHERE maquestionid = :questionId');
		$stmt->bindParam(':questionId', $questionId);
		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getSessionById($sessionId) {
		$stmt = $this->db->prepare("SELECT * FROM sessions WHERE id = :id");
		$stmt->execute(['id' => $sessionId]);
		return $stmt->fetch();
	}

	public function getSessionQuestionsCount($sessionId) {
		$stmt = $this->db->prepare("SELECT * FROM session_questions WHERE sessionid = :sessionid");
		$stmt->execute(['sessionid' => $sessionId]);
		return $stmt->fetchColumn();
	}

	public function saveAnswer($session_id, $answer, $answerVariant = null) {
		if ($answer['question_type'] === 'ma') {
			foreach ($answer['selected_variants'] as $variant) {
				$stmt = $this->db->prepare("INSERT INTO session_answers (session_id, session_question_id, maquestion_variant_id) VALUES (:session_id, :session_question_id, :maquestion_variant_id)");
				$stmt->execute([
					'session_id' => $session_id,
					'session_question_id' => $answer['question_id'],
					'maquestion_variant_id' => $variant['variant_id']
				]);
			}
		} else {
			$stmt = $this->db->prepare("INSERT INTO session_answers (session_id, session_question_id, answer) VALUES (:session_id, :session_question_id, :answer)");
			$stmt->execute([
				'session_id' => $session_id,
				'session_question_id' => $answer['question_id'],
				'answer' => $answerVariant
			]);
		}
	}

	public function updateSessionPoints($sessionId)
	{
		$session = $this->getSessionById($sessionId);
		if (!$session) {
			return false;
		}

		$totalPoints = 0;
		$questions = $this->getQuestionsBySessionId($sessionId);
		foreach ($questions as $question) {
			$answer = $this->getAnswerBySessionIdAndQuestionId($sessionId, $question['id']);
			if ($answer) {
				if ($question['type'] === 'ma') {
					$variants = $this->getVariantsByQuestionId($question['id']);
					$isCorrect = true;
					foreach ($variants as $variant) {
						if (($variant['is_correct'] && !in_array($variant['id'], $answer['selected_variants'])) ||
							(!$variant['is_correct'] && in_array($variant['id'], $answer['selected_variants']))
						) {
							$isCorrect = false;
							break;
						}
					}
					if ($isCorrect) {
						$totalPoints += $question['points'];
					}
				} elseif ($answer['answer'] === $question['answer']) {
					$totalPoints += $question['points'];
				}
			}
		}

		$stmt = $this->db->prepare("UPDATE sessions SET actual_points = :actualPoints WHERE id = :id");
		$stmt->bindParam(':actualPoints', $totalPoints);
		$stmt->bindParam(':id', $sessionId);
		return $stmt->execute();
	}

	public function endSession($sessionId) {
		$stmt = $this->db->prepare("UPDATE sessions SET is_finished = 1 WHERE id = :id");
		$stmt->execute(['id' => $sessionId]);
	}

	public function addQuestionsToSession($sessionId, $questionType, $questions) {
		$session = $this->getSessionById($sessionId);
		if (!$session) {
			throw new Exception("Session not found.");
		}

		$sessionModel = new Session($session['username'], 0, 0, $this->db, $sessionId);

		foreach ($questions as $question) {
			$sessionModel->addQuestion($questionType, $question);
		}
	}

	/**
	 * Returns an array with the sessions status and it's questions
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
				'questions' => $this->getSessionQuestions($sessionId)
			);
			var_dump($status['questions']);
			return $status;
		} catch (PDOException $e) {
			throw new Exception("Error getting session status: " . $e->getMessage());
		}
	}

	public function getSessionQuestions($sessionId)
	{
		$stmt = $this->db->prepare("
			SELECT
				sq.id AS session_question_id,
				IFNULL(mq.id, tfq.id) AS id,
				IFNULL(mq.text, tfq.text) AS text,
				mq.is_multiple,
				NULLIF(mqv.variant, '') AS variant,
				mqv.is_correct,
				tfq.answer,
				sq.question_type
			FROM
				session_questions AS sq
				LEFT JOIN maquestions AS mq ON mq.id = sq.maquestion_id
				LEFT JOIN maquestion_variants AS mqv ON mqv.maquestionid = mq.id
				LEFT JOIN tfquestions AS tfq ON tfq.id = sq.tfquestion_id
			WHERE
				sq.sessionid = :sessionid
		");
		$stmt->bindParam(":sessionid", $sessionId);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$questions = array();
		foreach ($rows as $row) {
			if ($row['question_type'] === 'tfquestion') {
				$question = array(
					'id' => $row['id'],
					'text' => $row['text'],
					'is_multiple' => null,
					'answer' => $row['answer']
				);
			} else {
				if (!isset($questions[$row['id']])) {
					$questions[$row['id']] = array(
						'id' => $row['id'],
						'text' => $row['text'],
						'is_multiple' => $row['is_multiple'],
						'variants' => array()
					);
				}
				if ($row['variant']) {
					$questions[$row['id']]['variants'][] = array(
						'variant' => $row['variant'],
						'is_correct' => $row['is_correct']
					);
				}
			}
		}

		return array_values($questions);
	}
}