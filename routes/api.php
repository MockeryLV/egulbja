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

    // Submit answers
    $group->post('/{sessionId}/answers', function ($request, $response, $args) use ($sessionController) {
        $data = $request->getParsedBody();
        $sessionController->submitAnswers($args['sessionId'], $data['answers']);
        return $response->withStatus(204);
    });

    // Get session status
    $group->get('/{sessionId}', function ($request, $response, $args) use ($sessionController) {
        $status = $sessionController->getSessionStatus($args['sessionId']);
        return $response->withJson($status);
    });

    $group->get('/current/id', function ($request, $response, $args) {
        // Return the current active session ID from the $_SESSION variable
        return $response->withJson(['sessionId' => $_SESSION['sessionId']]);
    });
});