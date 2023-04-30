<?php

namespace Models;

use JsonSerializable;
use PDO;

class MaQuestionVariant implements JsonSerializable
{
    private $maQuestionId;
    private $variant;
    private $isCorrect;

    function __construct($maQuestionId, $variant, $isCorrect)
    {
        $this->maQuestionId = $maQuestionId;
        $this->variant = $variant;
        $this->isCorrect = $isCorrect;
    }

    function getMaQuestionId()
    {
        return $this->maQuestionId;
    }

    function getVariant()
    {
        return $this->variant;
    }

    function getIsCorrect()
    {
        return $this->isCorrect;
    }

    public static function getVariantsForQuestion($db, $id)
    {
        $stmt = $db->prepare("SELECT * FROM maquestion_variants WHERE maquestionid = ?");
        $stmt->execute([$id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $variants = array();
        foreach ($results as $row) {
            $variant = new MaQuestionVariant(
                $row['maquestionid'],
                $row['variant'],
                $row['is_correct']
            );
            $variants[] = $variant;
        }

        return $variants;
    }

    public function jsonSerialize() {
        return [
            'variant' => $this->variant,
            'is_correct' => $this->isCorrect
        ];
    }
}

