<?php

namespace Utils;

use PDO;
use PDOException;

/**
 * A database utility class to manage database connections and queries.
 */
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    /**
     * Constructor method to create a new Database instance.
     *
     * @param array $config The configuration array containing database connection details.
     * @throws PDOException If the connection fails, a PDOException is thrown.
     */
    private function __construct(array $config) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8', $config['host'], $config['dbname']);

        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new PDOException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Returns a singleton instance of the Database class.
     *
     * @param array $config The configuration array containing database connection details.
     * @return Database The singleton instance of the Database class.
     */
    public static function getInstance(array $config): Database {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Returns the database connection.
     *
     * @return PDO The PDO object representing the database connection.
     */
    public function getConnection(): PDO {
        return $this->connection;
    }
}