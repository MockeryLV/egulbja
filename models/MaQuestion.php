<?php

namespace Models;

use JsonSerializable;
use PDO;
use PDOException;

require_once(__DIR__.'/../models/MaQuestionVariant.php');

/**
 * Represents a multiple-choice question with text and one or more answer options.
 */
class MaQuestion implements JsonSerializable
{
    private int $id;
    private string $text;
    private bool $isMultiple;
    private array $variants;

    /**
     * Initializes a new instance of the MaQuestion class.
     *
     * @param int $id The question ID.
     * @param string $text The question text.
     * @param bool $isMultiple Indicates whether the question allows multiple answers.
     * @param array $variants The answer options for the question.
     */
    public function __construct(int $id, string $text, bool $isMultiple, array $variants = [])
    {
        $this->id = $id;
        $this->text = $text;
        $this->isMultiple = $isMultiple;
        $this->variants = $variants;
    }

    /**
     * Gets the question ID.
     *
     * @return int The question ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets the question text.
     *
     * @return string The question text.
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Indicates whether the question allows multiple answers.
     *
     * @return bool True if the question allows multiple answers; otherwise, false.
     */
    public function getIsMultiple(): bool
    {
        return $this->isMultiple;
    }

    /**
     * Gets the answer options for the question.
     *
     * @return array The answer options for the question.
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * Sets the answer options for the question.
     *
     * @param array $variants The answer options for the question.
     */
    public function setVariants(array $variants): void
    {
        $this->variants = $variants;
    }

    /**
     * Adds an answer option to the question.
     *
     * @param string $variant The answer option to add.
     */
    public function addVariant(string $variant): void
    {
        $this->variants[] = $variant;
    }

    /**
     * Retrieves a MaQuestion object by its ID from the database.
     *
     * @param PDO $db The database connection object
     * @param int $id The ID of the MaQuestion to retrieve
     *
     * @return MaQuestion|null The MaQuestion object, or null if not found
     */
    public static function getById(PDO $db, int $id): ?MaQuestion
    {
        $query = "SELECT * FROM maquestions WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            // Handle database errors
            error_log('PDOException: ' . $e->getMessage());
            return null;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $question = new MaQuestion(
            $row['id'],
            $row['text'],
            $row['is_multiple'] == 1,
            MaQuestionVariant::getVariantsForQuestion($db, $row['id'])
        );

        return $question;
    }

    public static function getRandomQuestions(PDO $db, int $count): array
    {
        $query = "SELECT * FROM maquestions ORDER BY RAND() LIMIT :count";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':count', $count, PDO::PARAM_INT);
        $stmt->execute();

        $maQuestions = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $text = $row['text'];
            $isMultiple = ($row['is_multiple'] == 1);
            $variants = MaQuestionVariant::getVariantsForQuestion($db, $id);
            $maQuestion = new MaQuestion($id, $text, $isMultiple, $variants);
            $maQuestions[] = $maQuestion;
        }

        return $maQuestions;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'is_multiple' => $this->isMultiple,
            'variants' => $this->getVariants()
        ];
    }
}

