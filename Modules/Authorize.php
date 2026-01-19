<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\Cores\Controller;
use Lukiman\AuthServer\Libraries\AuthServerFactory;
use Lukiman\AuthServer\Libraries\Entities\UserEntity;
use Nyholm\Psr7\Response;
use League\OAuth2\Server\Exception\OAuthServerException;

class Authorize extends Controller
{
    public function do_Index()
    {
        $server = AuthServerFactory::create();
        
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
            // Validate the HTTP request and return an AuthorizationRequest object.
            $authRequest = $server->validateAuthorizationRequest($request);

            if ($request->getMethod() === 'POST') {
                // If it's a POST, assume user clicked "Approve"
                
                $user = new UserEntity();
                $user->setIdentifier(1); // Hardcoded User ID 1

                $authRequest->setUser($user);
                $authRequest->setAuthorizationApproved(true);

                return $server->completeAuthorizationRequest($authRequest, $response);
            }
            
            // If GET, show approval UI. 
            $response->getBody()->write('
                <form method="post">
                    <h1>Authorize App</h1>
                    <p>Client: ' . $authRequest->getClient()->getName() . '</p>
                    <button type="submit">Approve</button>
                    <!-- Include hidden fields if necessary, but query params are in URL -->
                </form>
            ');
            return $response;


        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (\Exception $exception) {
            $response->getBody()->write($exception->getMessage());
            return $response->withStatus(500);
        }
    }
}
