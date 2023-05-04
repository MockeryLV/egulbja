<?php

namespace Services;

use Exception;
use Models\MaQuestion;
use Models\Session;
use Models\TfQuestion;

class SessionService {
	private $sessionRepository;

	public function __construct($sessionRepository) {
		$this->sessionRepository = $sessionRepository;
	}

	public function createSession(string $username): int {
		try {
			// Create a new session object
			$session = new Session($username, 0, 0);

			// Insert the session into the database
			$sessionId = $this->sessionRepository->createSession($session);
			// Set the ID of the session object
			$session->setId($sessionId);

			// Add multiple-choice questions to the session
			$maQuestions = $this->sessionRepository->getRandomMaQuestions(2);
			$this->addQuestionsToSession($sessionId, 'ma', $maQuestions);

			// Add true/false questions to the session
			$tfQuestions = $this->sessionRepository->getRandomTfQuestions(2);
			$this->addQuestionsToSession($sessionId, 'tf', $tfQuestions);

			$questionsCount = $this->getSessionQuestionsCount($sessionId);

			$this->sessionRepository->updateSessionMaxPoints($sessionId, $questionsCount);

			return $sessionId;
		} catch (Exception $e) {
			// Log the error and re-throw the exception
			error_log($e->getMessage());
			throw new Exception('Error creating session');
		}
	}

	public function addQuestionsToSession($sessionId, $questionType, $questions) {
		$this->sessionRepository->addQuestionsToSession($sessionId, $questionType, $questions);
	}

	public function getSessionQuestions($sessionId) {
		return $this->sessionRepository->getSessionQuestions($sessionId);
	}

	public function getAnswerBySessionIdAndQuestionId($sessionId, $questionId) {
		return $this->sessionRepository->getAnswerBySessionIdAndQuestionId($sessionId, $questionId);
	}

	public function getVariantsByQuestionId($questionId) {
		return $this->sessionRepository->getVariantsByQuestionId($questionId);
	}

	public function getSessionById($sessionId) {
		return $this->sessionRepository->getSessionById($sessionId);
	}

	public function getSessionQuestionsCount($sessionId) {
		return $this->sessionRepository->getSessionQuestionsCount($sessionId);
	}

	public function getSessionStatus($sessionId) {
		return $this->sessionRepository->getSessionStatus($sessionId);
	}

	public function saveAnswerBySessionIdAndQuestionId($sessionId, $question, $answer) {
		$this->sessionRepository->saveAnswerBySessionIdAndQuestionId($sessionId, $question, $answer);
	}

	public function updateSessionActualPoints($sessionId, $currentPoints) {
		$this->sessionRepository->updateSessionActualPoints($sessionId, $currentPoints);
	}

	public function endSession($sessionId) {
		$this->sessionRepository->endSession($sessionId);
	}
}