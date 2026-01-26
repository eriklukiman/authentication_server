<?php

use Slim\Factory\AppFactory;
use App\Middleware\OAuthMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Public endpoint (no auth)
$app->get('/health', function ($request, $response) {
    $response->getBody()->write('OK');
    return $response;
});

// Protected upload endpoint
$app->post('/upload', function ($request, $response) {

    // Attributes injected by OAuth middleware
    $userId = $request->getAttribute('oauth_user_id');
    $scopes = $request->getAttribute('oauth_scopes');

    if (!in_array('basic', $scopes ?? [])) {
        $response->getBody()->write('Insufficient scope');
        return $response->withStatus(403);
    }

    $files = $request->getUploadedFiles();

    if (!isset($files['file'])) {
        $response->getBody()->write('No file uploaded');
        return $response->withStatus(400);
    }

    $file = $files['file'];
    $file->moveTo(__DIR__ . '/../uploads/' . $file->getClientFilename());

    $response->getBody()->write(json_encode([
        'status' => 'ok',
        'user_id' => $userId,
    ]));

    return $response->withHeader('Content-Type', 'application/json');
})
->add(new OAuthMiddleware('file://' . __DIR__ . '/../../public.key'));

$app->run();
