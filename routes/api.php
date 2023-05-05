<?php

use Slim\Http\ServerRequest;
use Slim\Routing\RouteCollectorProxy;
use Slim\Http\Response;
use Utils\Database;
use Tuupola\Middleware\CorsMiddleware;
use Validators\AnswersValidator;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../config/config.php';

// Initialize database connection
$db = \Utils\Database::getInstance($config['db'])->getConnection();

$app->add(new CorsMiddleware([
	"origin" => ["http://localhost:5173"],
	"methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
	"headers.allow" => ["Authorization", "If-Match", "Content-Type"],
	"headers.expose" => ["Etag"],
	"credentials" => true,
	"cache" => 86400
]));

// MaQuestion routes
$app->get('/maquestions/random/{num}', function ($request, $response, $args) use ($db) {
	$controller = new \Controllers\MaQuestionController($db);
	$questions = $controller->getRandomQuestions($args['num']);
	return $response->withJson($questions);
});

// TfQuestion routes
$app->get('/tfquestions/random/{num}', function ($request, $response, $args) use ($db) {
	$controller = new \Controllers\TfQuestionController($db);
	$questions = $controller->getRandomQuestions($args['num']);
	return $response->withJson($questions);
});

// Session routes
$app->group('/sessions', function (RouteCollectorProxy $group) use ($db) {
	$sessionController = new \Controllers\SessionController(new \Services\SessionService(new \Repositories\SessionRepository($db)));

	// Create session
	$group->post('', function ($request, $response, $args) use ($sessionController) {
		if (isset($_SESSION['sessionId'])) {
			return $response->withStatus(409)->withJson(['message' => 'Session already exists']);
		}
		$data = $request->getParsedBody();
		$sessionId = $sessionController->createSession($data['username']);

		// Save session ID in $_SESSION variable
		$_SESSION['sessionId'] = $sessionId;

		return $response->withJson(['sessionId' => $sessionId]);
	});

	// Get session status
	$group->get('/{sessionId}', function ($request, $response, $args) use ($sessionController) {
		$status = $sessionController->getSessionStatus($args['sessionId']);
		return $response->withJson($status);
	});

	// Get active session
	$group->get('', function ($request, $response, $args) use ($sessionController) {
		if (!isset($_SESSION['sessionId'])) {
			return $response->withStatus(404)->withJson(['message' => 'Session not found']);
		}
		$session = $sessionController->getSessionById($_SESSION['sessionId']);
		if (!$session || $session->getIsFinished()) {
			unset($_SESSION['sessionId']);
			return $response->withStatus(404)->withJson(['message' => 'Session not found']);
		}

		return $response->withJson(['sessionId' => $session->getIsFinished(), 'username' => $session->getUsername()]);
	});

	// Submit answers
	$group->post('/{sessionId}/answers', function ($request, $response, $args) use ($sessionController, $db) {
		$data = $request->getParsedBody();
		// Check if session exists and is not finished
		$session = $sessionController->getSessionById($args['sessionId']);
		if (!$session) {
			return $response->withStatus(404)->withJson(['message' => 'Session not found']);
		}
		if ($session->getIsFinished()) {
			return $response->withStatus(400)->withJson(['message' => 'Session is already finished']);
		}

		// Iterate through the answers and validate them
		$answers = $data['answers'];

		$validator = new AnswersValidator($db, $answers);
		if (!$validator->validate()) {
			return $response->withStatus(400)->withJson(['message' => 'Invalid answer']);
		}

		// Save the answers in the database
		$total_points = 0;
		foreach ($answers as $answer) {
			$question = null;
			$sessionQuestionStmt = $db->prepare("SELECT * FROM session_questions WHERE id = :id");
			$sessionQuestionStmt->bindParam(':id', $answer['question_id'], PDO::PARAM_INT);
			$sessionQuestionStmt->execute();
			$sessionQuestion = $sessionQuestionStmt->fetch(PDO::FETCH_ASSOC);
			if ($sessionQuestion['question_type'] === 'ma') {
				$maQuestionStmt = $db->prepare("SELECT * FROM maquestions WHERE id = :id");
				$maQuestionStmt->bindParam(':id', $sessionQuestion['maquestion_id'], PDO::PARAM_INT);
				$maQuestionStmt->execute();
				$question = $maQuestionStmt->fetch(PDO::FETCH_ASSOC);
			}
			elseif ($sessionQuestion['question_type'] === 'tf') {
				$tfQuestionStmt = $db->prepare("SELECT * FROM tfquestions WHERE id = :id");
				$tfQuestionStmt->bindParam(':id', $sessionQuestion['tfquestion_id'], PDO::PARAM_INT);
				$tfQuestionStmt->execute();
				$question = $tfQuestionStmt->fetch(PDO::FETCH_ASSOC);
			}

			if ($answer['question_type'] === 'ma') {
				$is_correct = true;
				foreach ($answer['selected_variants'] as $selectedVariant) {
					$query = "SELECT * FROM maquestion_variants 
					  INNER JOIN session_questions ON maquestion_variants.maquestion_id = session_questions.maquestion_id 
					  WHERE maquestion_variants.id = :variant_id AND session_questions.id = :session_question_id";
					$stmt = $db->prepare($query);
					$stmt->bindParam(':variant_id', $selectedVariant['variant_id'], PDO::PARAM_INT);
					$stmt->bindParam(':session_question_id', $answer['question_id'], PDO::PARAM_INT);
					$stmt->execute();
					$variant = $stmt->fetch(PDO::FETCH_ASSOC);
					if (!$variant) {
						return false;
					}
					if (!$variant['is_correct']) {
						$is_correct = false;
						break;
					}
				}
				if ($is_correct) {
					$total_points++;
				}
				$sessionController->saveAnswerBySessionIdAndQuestionId($session->getId(), $answer);
			}
			else {
				if ($answer['answer'] == $question['answer']) {
					$total_points++;
				}
				$sessionController->saveAnswerBySessionIdAndQuestionId($session->getId(), $answer, $answer['answer']);
			}
		}

		// Update the actual_points column for the session
		$sessionController->updateSessionActualPoints($session->getId(), $total_points);
		// End the session
		$sessionController->endSession($session->getId());
		unset($_SESSION['sessionId']);

		return $response->withJson(['message' => 'Answers submitted successfully']);
	});

});


