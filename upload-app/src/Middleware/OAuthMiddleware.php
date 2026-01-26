<?php

namespace App\Middleware;

use App\Repository\NullAccessTokenRepository;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class OAuthMiddleware implements MiddlewareInterface
{
    private ResourceServer $resourceServer;

    public function __construct(string $publicKeyPath)
    {
        $this->resourceServer = new ResourceServer(
            new NullAccessTokenRepository(),
            $publicKeyPath
        );
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            $request = $this->resourceServer
                ->validateAuthenticatedRequest($request);

            return $handler->handle($request);

        } catch (OAuthServerException $e) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'invalid_token',
                'message' => $e->getMessage(),
                'hint'    => $e->getHint(),
                'status'  => $e->getHttpStatusCode(),
            ]));

            return $response
                ->withStatus($e->getHttpStatusCode())
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
