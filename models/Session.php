<?php

namespace Models;

/**
 * Represents a session.
 */
class Session {
	private ?int $id;
	private string $username;
	private int $maxPoints;
	private ?int $actualPoints;
	private int $isFinished;

	/**
	 * Initializes a new instance of the Session class.
	 *
	 * @param string $username The username associated with the session.
	 * @param int $maxPoints The maximum number of points that can be earned in the session.
	 * @param int $actualPoints The actual number of points earned in the session.
	 * @param int|null $id The session ID, if it already exists.
	 */
	function __construct(string $username, int $maxPoints, ?int $actualPoints, int $isFinished = 0, ?int $id = null) {
		$this->id = $id;
		$this->username = $username;
		$this->maxPoints = $maxPoints;
		$this->actualPoints = $actualPoints;
		$this->isFinished = $isFinished;
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
	 * @return int
	 */
	public function getIsFinished(): int {
		return $this->isFinished;
	}

	/**
	 * Sets the session ID.
	 *
	 * @param int $id The session ID.
	 */
	function setId(int $id): void {
		$this->id = $id;
	}
}