<?php

namespace Controllers;

use Exception;
use Models\Session;
use Services\SessionService;

class SessionController
{
	private SessionService $sessionService;

	public function __construct(SessionService $sessionService)
	{
		$this->sessionService = $sessionService;
	}

	public function createSession(string $username): int
	{
		try {
			$sessionId = $this->sessionService->createSession($username);
			return $sessionId;
		} catch (Exception $e) {
			error_log($e->getMessage());
			throw new Exception('Error creating session');
		}
	}

	public function addQuestionsToSession(int $sessionId, string $questionType, array $questions): void
	{
		$this->sessionService->addQuestionsToSession($sessionId, $questionType, $questions);
	}

	public function getSessionQuestions(int $sessionId): array
	{
		return $this->sessionService->getSessionQuestions($sessionId);
	}

	public function getAnswerBySessionIdAndQuestionId(int $sessionId, int $questionId): ?string
	{
		return $this->sessionService->getAnswerBySessionIdAndQuestionId($sessionId, $questionId);
	}

	public function getVariantsByQuestionId(int $questionId): array
	{
		return $this->sessionService->getVariantsByQuestionId($questionId);
	}

	public function getSessionById(int $sessionId): Session
	{
		return $this->sessionService->getSessionById($sessionId);
	}

	public function getSessionQuestionsCount(int $sessionId): int
	{
		return $this->sessionService->getSessionQuestionsCount($sessionId);
	}

	public function getSessionStatus($sessionId) {
		return $this->sessionService->getSessionStatus($sessionId);
	}

	public function saveAnswerBySessionIdAndQuestionId(int $sessionId, array $question, ?string $answer = null): void
	{
		$this->sessionService->saveAnswerBySessionIdAndQuestionId($sessionId, $question, $answer);
	}

	public function updateSessionActualPoints($sessionId, $currentPoints) {
		$this->sessionService->updateSessionActualPoints($sessionId, $currentPoints);
	}

	public function endSession($sessionId) {
		$this->sessionService->endSession($sessionId);
	}
}
