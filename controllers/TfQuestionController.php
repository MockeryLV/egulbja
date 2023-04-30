<?php
namespace Controllers;

use PDO;
use Models\TfQuestion;

require_once(__DIR__ . '/../models/TfQuestion.php');

class TfQuestionController {
    private $db;

    function __construct($db) {
        $this->db = $db;
    }

    function getRandomQuestions($numQuestions) {
        $questions = array();
        $query = "SELECT * FROM tfquestions ORDER BY RAND() LIMIT :count";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':count', $numQuestions, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $questions[] = new TfQuestion($row['id'], $row['text'], $row['answer']);
        }
        return $questions;
    }
}
