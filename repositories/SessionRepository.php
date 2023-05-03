<?php

namespace Repositories;

use Models\Session;
use PDO;

class SessionRepository
{
	protected $db;

	public function __construct(PDO $db)
	{
		$this->db = $db;
	}

	public function create(Session $session): int
	{
		$stmt = $this->db->prepare("
            INSERT INTO sessions (username, max_points, actual_points)
            VALUES (:username, :maxPoints, :actualPoints)
        ");

		$stmt->execute([
			'username' => $session->getUsername(),
			'maxPoints' => $session->getMaxPoints(),
			'actualPoints' => $session->getActualPoints(),
		]);

		return (int) $this->db->lastInsertId();
	}

	public function getById(int $id): ?Session
	{
		$stmt = $this->db->prepare("
            SELECT * FROM sessions WHERE id = :id
        ");

		$stmt->execute(['id' => $id]);

		$session = $stmt->fetchObject(Session::class);

		return $session ?: null;
	}

	public function updateMaxPoints(int $sessionId, int $maxPoints): void
	{
		$stmt = $this->db->prepare("
            UPDATE sessions SET max_points = :maxPoints WHERE id = :sessionId
        ");

		$stmt->execute([
			'sessionId' => $sessionId,
			'maxPoints' => $maxPoints,
		]);
	}
}