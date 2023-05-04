<?php

namespace Repositories;

use Exception;
use Models\MaQuestion;
use Models\MaQuestionVariant;
use Models\TfQuestion;
use PDO;
use Models\Session;
use PDOException;

class SessionRepository
{
	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function createSession(Session $session): int {
		$query = "INSERT INTO sessions (username, max_points, actual_points) VALUES (:username, :max_points, :actual_points)";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':username', $session->getUsername());
		$stmt->bindValue(':max_points', $session->getMaxPoints());
		$stmt->bindValue(':actual_points', $session->getActualPoints());

		if ($stmt->execute()) {
			return $this->db->lastInsertId();
		}
		else {
			throw new Exception('Error creating session');
		}
	}

	public function addQuestionsToSession($sessionId, $questionType, $questions) {
		foreach ($questions as $question) {
			$query = "INSERT INTO session_questions (session_id, question_type, maquestion_id, tfquestion_id) VALUES (:session_id, :question_type, :maquestion_id, :tfquestion_id)";

			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':session_id', $sessionId);
			$stmt->bindValue(':question_type', $questionType);

			if ($questionType === 'ma') {
				$stmt->bindValue(':maquestion_id', $question->getId());
				$stmt->bindValue(':tfquestion_id', null);
			} else {
				$stmt->bindValue(':maquestion_id', null);
				$stmt->bindValue(':tfquestion_id', $question->getId());
			}

			if (!$stmt->execute()) {
				throw new Exception('Error adding questions to session');
			}
		}
	}


	public function getSessionQuestions($sessionId) {
		$query = "SELECT * FROM session_questions WHERE session_id = :session_id";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':session_id', $sessionId);
		$stmt->execute();

		$questions = array();

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$questionType = $row['question_type'];
			$questionId = $row['question_id'];

			if ($questionType === 'ma') {
				$question = MaQuestion::getById($questionId, $this->db);
			}
			else {
				$question = TfQuestion::getById($questionId, $this->db);
			}

			array_push($questions, $question);
		}

		return $questions;
	}

	public function getSessionQuestionsCount($sessionId) {
		$query = "SELECT COUNT(*) as count FROM session_questions WHERE session_id = :session_id";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':session_id', $sessionId);
		$stmt->execute();

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row['count'];
	}

	public function getAnswerBySessionIdAndQuestionId($sessionId, $questionId) {
		$query = "SELECT answer, answer_variant FROM session_answers WHERE session_id = :session_id AND question_id = :question_id";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':session_id', $sessionId);
		$stmt->bindValue(':question_id', $questionId);
		$stmt->execute();

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			return null;
		}

		return $row;
	}

	public function getVariantsByQuestionId($questionId) {
		$query = "SELECT * FROM variants WHERE question_id = :question_id";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':question_id', $questionId);
		$stmt->execute();

		$variants = array();

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			array_push($variants, $row['text']);
		}

		return $variants;
	}

	public function updateSessionMaxPoints($sessionId, $maxPoints) {
		$query = "UPDATE sessions SET max_points = :max_points WHERE id = :session_id";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':max_points', $maxPoints);
		$stmt->bindValue(':session_id', $sessionId);

		if (!$stmt->execute()) {
			throw new Exception('Error updating session max points');
		}
	}

	public function getSessionById($sessionId) {
		$query = "SELECT * FROM sessions WHERE id = :session_id";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':session_id', $sessionId);
		$stmt->execute();

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			return null;
		}

		$session = new Session(
			$row['username'],
			$row['max_points'],
			$row['actual_points'],
			$row['is_finished']
		);

		$session->setId($row['id']);

		return $session;
	}

	public function updateSessionActualPoints($sessionId, $currentPoints) {
		$query = "UPDATE sessions SET actual_points = :actual_points WHERE id = :session_id";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':actual_points', $currentPoints);
		$stmt->bindValue(':session_id', $sessionId);

		if (!$stmt->execute()) {
			throw new Exception('Error updating session current points');
		}
	}

	public function saveAnswer($sessionId, $questionId, $answer, $answerVariant) {
		$query = "INSERT INTO session_answers (session_id, question_id, answer, answer_variant) VALUES (:session_id, :question_id, :answer, :answer_variant)";

		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':session_id', $sessionId);
		$stmt->bindValue(':question_id', $questionId);
		$stmt->bindValue(':answer', $answer);
		$stmt->bindValue(':answer_variant', $answerVariant);

		if (!$stmt->execute()) {
			throw new Exception('Error saving answer');
		}
	}

	public function saveAnswerBySessionIdAndQuestionId($sessionId, $question, $answer) {
		if ($question['question_type'] == 'ma') {
			foreach ($question['selected_variants'] as $variant) {
				$stmt = $this->db->prepare("INSERT INTO session_answers (session_id, session_question_id, maquestion_variant_id) VALUES (:session_id, :session_question_id, :maquestion_variant_id)");
				$stmt->execute([
					'session_id' => $sessionId,
					'session_question_id' => $question['question_id'],
					'maquestion_variant_id' => $variant['variant_id']
				]);
			}
		} else {
			$stmt = $this->db->prepare("INSERT INTO session_answers (session_id, session_question_id, answer) VALUES (:session_id, :session_question_id, :answer)");
			$stmt->execute([
				'session_id' => $sessionId,
				'session_question_id' => $question['question_id'],
				'answer' => $answer
			]);
		}
	}

	/**
	 * Retrieves a MaQuestion object by its ID from the database.
	 *
	 * @param PDO $db The database connection object
	 * @param int $id The ID of the MaQuestion to retrieve
	 *
	 * @return MaQuestion|null The MaQuestion object, or null if not found
	 */
	public function getMaQuestionsById(int $id): ?MaQuestion
	{
		$query = "SELECT * FROM maquestions WHERE id = :id";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);

		try {
			$stmt->execute();
		} catch (PDOException $e) {
			// Handle database errors
			error_log('PDOException: ' . $e->getMessage());
			return null;
		}

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			return null;
		}

		$question = new MaQuestion(
			$row['id'],
			$row['text'],
			$row['is_multiple'] == 1,
			MaQuestionVariant::getVariantsForQuestion($this->db, $row['id'])
		);

		return $question;
	}

	public function getRandomMaQuestions(int $count): array
	{
		$query = "SELECT * FROM maquestions ORDER BY RAND() LIMIT :count";
		$stmt = $this->db->prepare($query);
		$stmt->bindValue(':count', $count, PDO::PARAM_INT);
		$stmt->execute();

		$maQuestions = [];

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$id = $row['id'];
			$text = $row['text'];
			$isMultiple = ($row['is_multiple'] == 1);
			$variants = MaQuestionVariant::getVariantsForQuestion($this->db, $id);
			$maQuestion = new MaQuestion($id, $text, $isMultiple, $variants);
			$maQuestions[] = $maQuestion;
		}

		return $maQuestions;
	}

	/**
	 * Get a TfQuestion object by its ID
	 * @param PDO $db
	 * @param int $id
	 * @return TfQuestion|null
	 * @throws PDOException
	 */
	public function getTfQuestionById(PDO $db, int $id): ?TfQuestion
	{
		try {
			$stmt = $db->prepare('SELECT * FROM tfquestions WHERE id = :id');
			$stmt->bindParam(':id', $id, PDO::PARAM_INT);
			$stmt->execute();

			if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$id = $row['id'];
				$text = $row['text'];
				$answer = $row['answer'];
				$tfQuestion = new TfQuestion($id, $text, $answer);
				return $tfQuestion;
			} else {
				return null;
			}
		} catch (PDOException $e) {
			throw new PDOException($e->getMessage(), (int)$e->getCode());
		}
	}

	/**
	 * Get a number of random TfQuestion objects
	 * @param PDO $db
	 * @param int $count
	 * @return TfQuestion[]
	 * @throws PDOException
	 */
	public function getRandomTfQuestions(int $count): array {
		try {
			$stmt = $this->db->prepare('SELECT * FROM tfquestions ORDER BY RAND() LIMIT :count');
			$stmt->bindParam(':count', $count, PDO::PARAM_INT);
			$stmt->execute();
			$questions = array();
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$questions[] = new TfQuestion($row['id'], $row['text'], $row['answer']);
			}
			return $questions;
		} catch (PDOException $e) {
			throw new PDOException($e->getMessage(), (int)$e->getCode());
		}
	}

	public function endSession($sessionId) {
		$stmt = $this->db->prepare("UPDATE sessions SET is_finished = 1 WHERE id = :id");
		$stmt->execute(['id' => $sessionId]);
	}
}