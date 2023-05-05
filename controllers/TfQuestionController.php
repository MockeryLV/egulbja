<?php

namespace Controllers;

use PDO;
use Models\TfQuestion;
use PDOException;

/**
 * Controller for True/False questions.
 */
class TfQuestionController
{
	private PDO $db;

	/**
	 * Creates a new TfQuestionController instance.
	 *
	 * @param PDO $db  The PDO database connection.
	 */
	public function __construct(PDO $db) {
		$this->db = $db;
	}

	/**
	 * Gets a random set of True/False questions.
	 *
	 * @param int $numQuestions  The number of questions to retrieve.
	 *
	 * @throws PDOException If there's an error with the database connection.
	 * @return array An array of TfQuestion instances.
	 *
	 */
	public function getRandomQuestions(int $numQuestions): array {
		$questions = [];

		try {
			$query = "SELECT * FROM tfquestions ORDER BY RAND() LIMIT :count";
			$stmt = $this->db->prepare($query);
			$stmt->bindParam(':count', $numQuestions, PDO::PARAM_INT);
			$stmt->execute();

			while ($row = $stmt->fetch()) {
				$questions[] = new TfQuestion($row['id'], $row['text'], $row['answer']);
			}
		} catch (PDOException $e) {
			// Handle the database error appropriately
			throw new PDOException("Error retrieving questions: ".$e->getMessage());
		}

		return $questions;
	}
}
