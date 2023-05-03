<?php

namespace Models;

use PDO;
use PDOException;

/**
 * Represents a session.
 */
class Session {
    private ?int $id;
    private string $username;
    private int $maxPoints;
    private int $actualPoints;
    private PDO $db;

    /**
     * Initializes a new instance of the Session class.
     *
     * @param string $username The username associated with the session.
     * @param int $maxPoints The maximum number of points that can be earned in the session.
     * @param int $actualPoints The actual number of points earned in the session.
     * @param PDO $db The PDO database connection.
     * @param int|null $id The session ID, if it already exists.
     */
    function __construct(string $username, int $maxPoints, int $actualPoints, PDO $db, ?int $id = null) {
        $this->id = $id;
        $this->username = $username;
        $this->maxPoints = $maxPoints;
        $this->actualPoints = $actualPoints;
        $this->db = $db;
    }

    /**
     * Gets the session ID.
     *
     * @return int The session ID.
     */
    function getId(): int {
        return $this->id;
    }

    /**
     * Gets the username associated with the session.
     *
     * @return string The username associated with the session.
     */
    function getUsername(): string {
        return $this->username;
    }

    /**
     * Gets the maximum number of points that can be earned in the session.
     *
     * @return int The maximum number of points that can be earned in the session.
     */
    function getMaxPoints(): int {
        return $this->maxPoints;
    }

    /**
     * Gets the actual number of points earned in the session.
     *
     * @return int The actual number of points earned in the session.
     */
    function getActualPoints(): int {
        return $this->actualPoints;
    }

    /**
     * Sets the session ID.
     *
     * @param int $id The session ID.
     */
    function setId(int $id): void {
        $this->id = $id;
    }

	public function addQuestion(string $questionType, $question): void
	{
		try {
			$query = "INSERT INTO session_questions (sessionid, question_type, ";
			$query .= $questionType === 'ma' ? "maquestion_id)" : "tfquestion_id)";
			$query .= " VALUES (:session_id, :question_type, :question_id)";
			$stmt = $this->db->prepare($query);

			$sessionId = $this->getId();
			$questionId = $question->getId();

			$stmt->bindParam(":session_id", $sessionId, PDO::PARAM_INT);
			$stmt->bindParam(":question_type", $questionType, PDO::PARAM_STR);
			$stmt->bindParam(":question_id", $questionId, PDO::PARAM_INT);
			$stmt->execute();
		} catch (PDOException $e) {
			// Handle the exception here, such as logging the error or throwing a custom exception
			// to be caught by the calling function.
			throw new PDOException($e->getMessage());
		}
	}
}