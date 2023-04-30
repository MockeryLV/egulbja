<?php

namespace Models;

use JsonSerializable;
use PDO;

class TfQuestion implements JsonSerializable {
    private $id;
    private $text;
    private $answer;

    function __construct($id, $text, $answer) {
        $this->id = $id;
        $this->text = $text;
        $this->answer = $answer;
    }

    public function getId() {
        return $this->id;
    }

    public function getText() {
        return $this->text;
    }

    public function getAnswer() {
        return $this->answer;
    }

    public static function getById($db, $id)
    {
        $stmt = $db->prepare('SELECT * FROM tfquestions WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $text = $row['text'];
            $answer = $row['answer'];
            $tfQuestion = new TfQuestion($id, $text, $answer);
            return $tfQuestion;
        } else {
            return null;
        }
    }

    public static function getRandomQuestions($db, $count) {
        $stmt = $db->prepare('SELECT * FROM tfquestions ORDER BY RAND() LIMIT :count');
        $stmt->bindParam(':count', $count, PDO::PARAM_INT);
        $stmt->execute();
        $questions = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questions[] = new TfQuestion($row['id'], $row['text'], $row['answer']);
        }
        return $questions;
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'answer' => $this->answer
        ];
    }
}