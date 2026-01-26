<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\BaseController;
use Lukiman\AuthServer\Libraries\AuthServerFactory;
use Nyholm\Psr7\Response;
use League\OAuth2\Server\Exception\OAuthServerException;

class AccessToken extends BaseController
{
    public function do_Index()
    {
        $server = AuthServerFactory::create();
        $response = new Response();

        try {
            return $server->respondToAccessTokenRequest($this->psrRequest, $response);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (\Exception $exception) {
            $response->getBody()->write($exception->getMessage());
            return $response->withStatus(500);
        }
    }
}
