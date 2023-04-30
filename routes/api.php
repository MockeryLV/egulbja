<?php

use Slim\Http\ServerRequest;
use Slim\Routing\RouteCollectorProxy;
use Slim\Http\Response;
use Utils\Database;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/config.php';

// Initialize database connection
$db = \Utils\Database::getInstance($config['db'])->getConnection();

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
        $data = $request->getParsedBody();
        $sessionId = $sessionController->createSession($data['username']);

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
});