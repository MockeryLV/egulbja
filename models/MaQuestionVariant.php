<?php

namespace Models;

use JsonSerializable;
use PDO;
use PDOException;

class MaQuestionVariant implements JsonSerializable
{
    /** @var int */
    private $maQuestionId;

    /** @var string */
    private $variant;

    /** @var bool */
    private $isCorrect;

    /**
     * MaQuestionVariant constructor.
     *
     * @param int $maQuestionId
     * @param string $variant
     * @param bool $isCorrect
     */
    public function __construct(int $maQuestionId, string $variant, bool $isCorrect)
    {
        $this->maQuestionId = $maQuestionId;
        $this->variant = $variant;
        $this->isCorrect = $isCorrect;
    }

    /**
     * Get the ID of the question associated with this variant.
     *
     * @return int
     */
    public function getMaQuestionId(): int
    {
        return $this->maQuestionId;
    }

    /**
     * Get the text of the variant.
     *
     * @return string
     */
    public function getVariant(): string
    {
        return $this->variant;
    }

    /**
     * Check if this variant is correct.
     *
     * @return bool
     */
    public function getIsCorrect(): bool
    {
        return $this->isCorrect;
    }

    /**
     * Get all variants for a given question ID from the database.
     *
     * @param PDO $db
     * @param int $id
     * @return MaQuestionVariant[]
     * @throws PDOException if the database query fails
     */
    public static function getVariantsForQuestion(PDO $db, int $id): array
    {
        $stmt = $db->prepare("SELECT * FROM maquestion_variants WHERE maquestion_id = :maquestion_id");
        $stmt->bindParam(':maquestion_id', $id);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $variants = array();
        foreach ($results as $row) {
            $variant = new MaQuestionVariant(
                $row['maquestion_id'],
                $row['variant'],
                $row['is_correct']
            );
            $variants[] = $variant;
        }

        return $variants;
    }

    /**
     * Convert this object to a JSON-serializable array.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'variant' => $this->variant,
            'is_correct' => $this->isCorrect
        ];
    }
}