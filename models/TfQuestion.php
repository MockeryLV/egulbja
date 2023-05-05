<?php

namespace Models;

use JsonSerializable;
use PDO;
use PDOException;

/**
 * Class TfQuestion
 *
 * @package Models
 */
class TfQuestion implements JsonSerializable
{
	private int $id;
	private string $text;
	private bool $answer;

	/**
	 * TfQuestion constructor.
	 *
	 * @param int    $id
	 * @param string $text
	 * @param bool   $answer
	 */
	public function __construct(int $id, string $text, bool $answer) {
		$this->id = $id;
		$this->text = $text;
		$this->answer = $answer;
	}

	/**
	 * Get the ID of the question
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Get the text of the question
	 *
	 * @return string
	 */
	public function getText(): string {
		return $this->text;
	}

	/**
	 * Get the answer of the question
	 *
	 * @return bool
	 */
	public function getAnswer(): bool {
		return $this->answer;
	}

	/**
	 * Sets the id of the question.
	 *
	 * @param int $id  The id for the question.
	 */
	public function setId(int $id): void {
		$this->id = $id;
	}

	/**
	 * Get a TfQuestion object by its ID
	 *
	 * @param PDO $db
	 * @param int $id
	 *
	 * @throws PDOException
	 * @return TfQuestion|null
	 */
	public static function getById(PDO $db, int $id): ?TfQuestion {
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
			}
			else {
				return null;
			}
		} catch (PDOException $e) {
			throw new PDOException($e->getMessage(), (int)$e->getCode());
		}
	}

	/**
	 * Get a number of random TfQuestion objects
	 *
	 * @param PDO $db
	 * @param int $count
	 *
	 * @throws PDOException
	 * @return TfQuestion[]
	 */
	public static function getRandomQuestions(PDO $db, int $count): array {
		try {
			$stmt = $db->prepare('SELECT * FROM tfquestions ORDER BY RAND() LIMIT :count');
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

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'text' => $this->text,
			'answer' => $this->answer
		];
	}
}