<?php

namespace Models;

use JsonSerializable;
use PDO;
use PDOException;

class SessionAnswer implements JsonSerializable
{
	private int $id;
	private int $sessionId;
	private int $questionId;
	private string $answer;

	/**
	 * Initializes a new instance of the SessionAnswer class.
	 *
	 * @param int $id The answer ID.
	 * @param int $sessionId The ID of the session that the answer belongs to.
	 * @param int $questionId The ID of the question that the answer belongs to.
	 * @param string $answer The text of the answer.
	 */
	public function __construct(int $id, int $sessionId, int $questionId, string $answer)
	{
		$this->id = $id;
		$this->sessionId = $sessionId;
		$this->questionId = $questionId;
		$this->answer = $answer;
	}

	/**
	 * Gets the answer ID.
	 *
	 * @return int The answer ID.
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * Gets the ID of the session that the answer belongs to.
	 *
	 * @return int The ID of the session that the answer belongs to.
	 */
	public function getSessionId(): int
	{
		return $this->sessionId;
	}

	/**
	 * Gets the ID of the question that the answer belongs to.
	 *
	 * @return int The ID of the question that the answer belongs to.
	 */
	public function getQuestionId(): int
	{
		return $this->questionId;
	}

	/**
	 * Gets the text of the answer.
	 *
	 * @return string The text of the answer.
	 */
	public function getAnswer(): string
	{
		return $this->answer;
	}

	/**
	 * Saves the SessionAnswer object to the database.
	 *
	 * @param PDO $db The database connection object.
	 *
	 * @return bool True if the save was successful; otherwise, false.
	 */
	public function save(PDO $db): bool
	{
		$query = "INSERT INTO session_answers (session_id, question_id, answer) VALUES (:session_id, :question_id, :answer)";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':session_id', $this->sessionId, PDO::PARAM_INT);
		$stmt->bindParam(':question_id', $this->questionId, PDO::PARAM_INT);
		$stmt->bindParam(':answer', $this->answer, PDO::PARAM_STR);

		try {
			return $stmt->execute();
		} catch (PDOException $e) {
			// Handle database errors
			error_log('PDOException: ' . $e->getMessage());
			return false;
		}
	}

	public function jsonSerialize()
	{
		return [
			'id' => $this->id,
			'session_id' => $this->sessionId,
			'question_id' => $this->questionId,
			'answer' => $this->answer,
		];
	}
}