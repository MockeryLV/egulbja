<?php

namespace Validators;

use PDO;

class AnswersValidator {
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
			$sessionQuestion = $this->db->query("SELECT * FROM session_questions WHERE id = {$answer['question_id']}")->fetch(PDO::FETCH_ASSOC);

			if ($sessionQuestion['question_type'] === 'ma') {
				$question = $this->db->query("SELECT * FROM maquestions WHERE id = {$sessionQuestion['maquestion_id']}")->fetch(PDO::FETCH_ASSOC);
			}
			elseif ($sessionQuestion['question_type'] === 'tf') {
				$question = $this->db->query("SELECT * FROM tfquestions WHERE id = {$sessionQuestion['tfquestion_id']}")->fetch(PDO::FETCH_ASSOC);
			}
			if (!$question) {
				return false;
			}

			// Check if answer is valid
			if ($sessionQuestion['question_type'] === 'ma') {
				foreach ($answer['selected_variants'] as $selectedVariant) {
					$variant = $this->db->query("SELECT * FROM maquestion_variants WHERE maquestion_id = {$selectedVariant['maquestion_id']} AND id = {$selectedVariant['variant_id']}")->fetch(PDO::FETCH_ASSOC);
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
