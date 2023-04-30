<?php

namespace Models;

use JsonSerializable;
use PDO;

require_once(__DIR__.'/../models/MaQuestionVariant.php');

class MaQuestion implements JsonSerializable
{
    private $id;
    private $text;
    private $isMultiple;
    private $variants;

    function __construct($id, $text, $isMultiple, $variants = array())
    {
        $this->id = $id;
        $this->text = $text;
        $this->isMultiple = $isMultiple;
        $this->variants = $variants;
    }

    function getId()
    {
        return $this->id;
    }

    function getText()
    {
        return $this->text;
    }

    function getIsMultiple()
    {
        return $this->isMultiple;
    }

    function getVariants()
    {
        return $this->variants;
    }

    function setVariants($variants)
    {
        $this->variants = $variants;
    }

    function addVariant($variant)
    {
        $this->variants[] = $variant;
    }

    public static function getById($db, $id) {
        $query = "SELECT * FROM maquestions WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $id = $row['id'];
        $text = $row['text'];
        $isMultiple = ($row['is_multiple'] == 1);
        $variants = MaQuestionVariant::getVariantsForQuestion($db, $id);

        $questionData = array(
            'id' => $id,
            'text' => $text,
            'variants' => $variants
        );

        return $questionData;
    }

    public static function getRandomQuestions($db, $count)
    {
        $query = "SELECT * FROM maquestions ORDER BY RAND() LIMIT :count";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':count', $count, PDO::PARAM_INT);
        $stmt->execute();

        $maQuestions = array();

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

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'is_multiple' => $this->isMultiple
        ];
    }
}

