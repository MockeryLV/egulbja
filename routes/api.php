<?php

use Slim\Http\ServerRequest;
use Slim\Routing\RouteCollectorProxy;
use Slim\Http\Response;
use Utils\Database;
use Tuupola\Middleware\CorsMiddleware;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

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
	$sessionController = new \Controllers\SessionController($db);

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

	// Get active session
	$group->get('', function ($request, $response, $args) use ($sessionController) {
		if (!isset($_SESSION['sessionId'])) {
			return $response->withStatus(404)->withJson(['message' => 'Session not found']);
		}
		$session = $sessionController->getSessionById($_SESSION['sessionId']);
		if (!$session || $session['is_finished']) {
			unset($_SESSION['sessionId']);
			return $response->withStatus(404)->withJson(['message' => 'Session not found']);
		}

		return $response->withJson(['sessionId' => $session['id'], 'username' => $session['username']]);
	});

	// Get session status
	$group->get('/{sessionId}', function ($request, $response, $args) use ($sessionController) {
		$status = $sessionController->getSessionStatus($args['sessionId']);
		return $response->withJson($status);
	});

	// Submit answers
	$group->post('/{sessionId}/answers', function ($request, $response, $args) use ($sessionController, $db) {
		$data = $request->getParsedBody();
		// Check if session exists and is not finished
		$session = $sessionController->getSessionById($args['sessionId']);
		if (!$session) {
			return $response->withStatus(404)->withJson(['message' => 'Session not found']);
		}
		if ($session['is_finished']) {
			return $response->withStatus(400)->withJson(['message' => 'Session is already finished']);
		}

		// Iterate through the answers and validate them
		$answers = $data['answers'];
		$total_points = 0;
		foreach ($answers as $answer) {
			// Check if question exists
			$question = null;
			if ($answer['question_type'] === 'ma') {
				$question = $db->query("SELECT * FROM maquestions WHERE id = {$answer['question_id']}")->fetch();
			}
			elseif ($answer['question_type'] === 'tf') {
				$question = $db->query("SELECT * FROM tfquestions WHERE id = {$answer['question_id']}")->fetch();
			}
			if (!$question) {
				return $response->withStatus(400)->withJson(['message' => 'Invalid question ID']);
			}

			// Check if answer is valid
			if ($answer['question_type'] === 'ma') {
				$correctAnswerCount = $db->query("SELECT COUNT(*) FROM maquestion_variants WHERE maquestionid = {$answer['question_id']} AND is_correct = 1")->fetchColumn();
				$selectedAnswerCount = count($answer['selected_variants']);
				if ($selectedAnswerCount !== $correctAnswerCount) {
					return $response->withStatus(400)->withJson(['message' => 'Invalid answer']);
				}
				foreach ($answer['selected_variants'] as $selectedVariant) {
					$variant = $db->query("SELECT * FROM maquestion_variants WHERE id = {$selectedVariant['variant_id']}")->fetch();
					if (!$variant || $variant['maquestionid'] !== $answer['question_id'] || !$variant['is_correct']) {
						return $response->withStatus(400)->withJson(['message' => 'Invalid answer']);
					}
				}
				$points = $question['points'];
				$total_points += $points;
			}
			elseif ($answer['question_type'] === 'tf') {
				$question = $db->query("SELECT * FROM tfquestions WHERE id = {$answer['question_id']}")->fetch();
				if ($answer['answer'] !== $question['answer']) {
					return $response->withStatus(400)->withJson(['message' => 'Invalid answer']);
				}
				$points = $question['points'];
				$total_points += $points;
			}
		}

		// Save the answers in the database
		foreach ($answers as $answer) {
			if ($answer['question_type'] === 'ma') {
				$sessionController->saveAnswer($session['id'], $answer);
			} else {
				$sessionController->saveAnswer($session['id'], $answer, $answer['answer']);
			}
		}

		// Update the actual_points column for the session
		$sessionController->updateSessionPoints($session['id']);
		// End the session
		$sessionController->endSession($session['id']);
		unset($_SESSION['sessionId']);

		return $response->withJson(['message' => 'Answers submitted successfully']);
	});

	// End session
	$group->post('/{sessionId}/end', function ($request, $response, $args) use ($sessionController) {
		// Check if session exists and is not finished
		$session = $sessionController->getSessionById($args['sessionId']);
		if (!$session) {
			return $response->withStatus(404)->withJson(['message' => 'Session not found']);
		}
		if ($session['is_finished']) {
			return $response->withStatus(400)->withJson(['message' => 'Session is already finished']);
		}

		// End the session
		$sessionController->endSession($session['id']);
		unset($_SESSION['sessionId']);

		return $response->withJson(['message' => 'Session ended successfully']);
	});
});

