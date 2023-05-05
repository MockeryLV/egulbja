<?php

namespace Controllers;

use Models\MaQuestion;
use Models\MaQuestionVariant;
use PDO;
use PDOException;

require_once(__DIR__.'/../models/MaQuestion.php');
require_once(__DIR__.'/../models/MaQuestionVariant.php');

/**
 * Class MaQuestionController
 *
 * @package Controllers
 */
class MaQuestionController
{
	/**
	 * @var PDO
	 */
	private $db;

	/**
	 * MaQuestionController constructor.
	 *
	 * @param PDO $db
	 */
	public function __construct(PDO $db) {
		$this->db = $db;
	}

	/**
	 * @param int $numQuestions
	 *
	 * @throws PDOException
	 * @return array
	 */
	public function getRandomQuestions(int $numQuestions): array {
		$questions = [];
		$query = "SELECT * FROM maquestions ORDER BY RAND() LIMIT :count";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':count', $numQuestions, PDO::PARAM_INT);
		$stmt->execute();
		while ($row = $stmt->fetch()) {
			$question = new MaQuestion($row['id'], $row['text'], $row['is_multiple']);
			$variants = [];
			$query = "SELECT * FROM maquestion_variants WHERE maquestion_id = :id";
			$variantStmt = $this->db->prepare($query);
			$variantStmt->bindParam(":id", $row['id'], PDO::PARAM_INT);
			$variantStmt->execute();
			while ($variantRow = $variantStmt->fetch()) {
				$variants[] = new MaQuestionVariant($variantRow['maquestion_id'], $variantRow['variant'], $variantRow['is_correct']);
			}
			$question->setVariants($variants);
			$questions[] = $question;
		}
		$result = [];
		foreach ($questions as $question) {
			$variants = [];
			foreach ($question->getVariants() as $variant) {
				$variants[] = [
					'variant' => $variant->getVariant(),
					'is_correct' => $variant->getIsCorrect()
				];
			}
			$result[] = [
				'id' => $question->getId(),
				'text' => $question->getText(),
				'variants' => $variants
			];
		}
		return $result;
	}
}