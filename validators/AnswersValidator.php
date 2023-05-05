<?php

namespace Validators;

use PDO;

class AnswersValidator
{
	private $db;
	private $answers;

	public function __construct(PDO $db, array $answers) {
		$this->db = $db;
		$this->answers = $answers;
	}

	public function validate() {
		foreach ($this->answers as $answer) {
			// Check if question exists

			$question = null;
			$sessionQuestionStmt = $this->db->prepare("SELECT * FROM session_questions WHERE id = :question_id");
			$sessionQuestionStmt->bindParam(':question_id', $answer['question_id'], PDO::PARAM_INT);
			$sessionQuestionStmt->execute();
			$sessionQuestion = $sessionQuestionStmt->fetch(PDO::FETCH_ASSOC);

			if ($sessionQuestion['question_type'] === 'ma') {
				$questionStmt = $this->db->prepare("SELECT * FROM maquestions WHERE id = :maquestion_id");
				$questionStmt->bindParam(':maquestion_id', $sessionQuestion['maquestion_id'], PDO::PARAM_INT);
				$questionStmt->execute();
				$question = $questionStmt->fetchAll(PDO::FETCH_ASSOC);
			}
			elseif ($sessionQuestion['question_type'] === 'tf') {
				$questionStmt = $this->db->prepare("SELECT * FROM tfquestions WHERE id = :tfquestion_id");
				$questionStmt->bindParam(':tfquestion_id', $sessionQuestion['tfquestion_id'], PDO::PARAM_INT);
				$questionStmt->execute();
				$question = $questionStmt->fetch(PDO::FETCH_ASSOC);
			}
			if (!$question) {
				return false;
			}

			// Check if answer is valid
			if ($sessionQuestion['question_type'] === 'ma') {
				foreach ($answer['selected_variants'] as $selectedVariant) {
					$query = "SELECT * FROM maquestion_variants 
					  INNER JOIN session_questions ON maquestion_variants.maquestion_id = session_questions.maquestion_id 
					  WHERE maquestion_variants.id = :variant_id AND session_questions.id = :session_question_id";
					$stmt = $this->db->prepare($query);
					$stmt->bindParam(':variant_id', $selectedVariant['variant_id'], PDO::PARAM_INT);
					$stmt->bindParam(':session_question_id', $answer['question_id'], PDO::PARAM_INT);
					$stmt->execute();
					$variant = $stmt->fetch(PDO::FETCH_ASSOC);
					if (!$variant) {
						return false;
					}
				}
			}
			elseif ($sessionQuestion['question_type'] === 'tf') {
				if ($answer['answer'] !== 1 && $answer['answer'] !== 0) {
					return false;
				}
			}
		}
		return true;
	}
}
