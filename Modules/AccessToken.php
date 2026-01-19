<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\Cores\Controller;
use Lukiman\AuthServer\Libraries\AuthServerFactory;
use Nyholm\Psr7\Response;
use League\OAuth2\Server\Exception\OAuthServerException;

class AccessToken extends Controller
{
    public function do_Index()
    {
        $server = AuthServerFactory::create();

        // Convert framework request to PSR-7 request
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $request = $creator->fromGlobals();
        $response = new Response();

        try {
            return $server->respondToAccessTokenRequest($request, $response);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (\Exception $exception) {
            $response->getBody()->write($exception->getMessage());
            return $response->withStatus(500);
        }
    }
}
