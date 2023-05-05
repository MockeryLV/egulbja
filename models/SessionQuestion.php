<?php

namespace Models;

class SessionQuestion
{
	private int $sessionId;
	private string $questionType;
	private int $questionId;

	/**
	 * SessionQuestion constructor.
	 *
	 * @param int    $sessionId
	 * @param string $questionType
	 * @param int    $questionId
	 */
	public function __construct(int $sessionId, string $questionType, int $questionId) {
		$this->sessionId = $sessionId;
		$this->questionType = $questionType;
		$this->questionId = $questionId;
	}

	/**
	 * Get the session ID.
	 *
	 * @return int
	 */
	public function getSessionId(): int {
		return $this->sessionId;
	}

	/**
	 * Get the question type.
	 *
	 * @return string
	 */
	public function getQuestionType(): string {
		return $this->questionType;
	}

	/**
	 * Get the question ID.
	 *
	 * @return int
	 */
	public function getQuestionId(): int {
		return $this->questionId;
	}
}