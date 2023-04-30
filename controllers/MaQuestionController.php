<?php
namespace Controllers;

use Models\MaQuestion;
use Models\MaQuestionVariant;
use PDO;

require_once(__DIR__ . '/../models/MaQuestion.php');
require_once(__DIR__ . '/../models/MaQuestionVariant.php');

class MaQuestionController {
    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    function getRandomQuestions($numQuestions) {
        $questions = array();
        $query = "SELECT * FROM maquestions ORDER BY RAND() LIMIT :count";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':count', $numQuestions, PDO::PARAM_INT);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $question = new MaQuestion($row['id'], $row['text'], $row['is_multiple']);
            $variants = array();
            $query = "SELECT * FROM maquestion_variants WHERE maquestionid = :id";
            $variantStmt = $this->db->prepare($query);
            $variantStmt->bindParam(":id", $row['id'], PDO::PARAM_INT);
            $variantStmt->execute();
            while ($variantRow = $variantStmt->fetch()) {
                $variants[] = new MaQuestionVariant($variantRow['maquestionid'], $variantRow['variant'], $variantRow['is_correct']);
            }
            $question->setVariants($variants);
            $questions[] = $question;
        }
        $result = array();
        foreach ($questions as $question) {
            $variants = array();
            foreach ($question->getVariants() as $variant) {
                $variants[] = array(
                    'variant' => $variant->getVariant(),
                    'is_correct' => $variant->getIsCorrect()
                );
            }
            $result[] = array(
                'id' => $question->getId(),
                'text' => $question->getText(),
                'variants' => $variants
            );
        }
        return $result;
    }
}
