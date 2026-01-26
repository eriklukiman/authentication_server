<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\BaseController;
use Lukiman\AuthServer\Libraries\AuthServerFactory;
use Lukiman\AuthServer\Libraries\Repositories\UserRepository;
use Nyholm\Psr7\Response;
use League\OAuth2\Server\Exception\OAuthServerException;

class Authorize extends BaseController
{
    public function do_Index()
    {
        $server = AuthServerFactory::create();
        $response = new Response();
        $request = $this->psrRequest;

        try {
            // Validate the HTTP request and return an AuthorizationRequest object.
            $authRequest = $server->validateAuthorizationRequest($request);
            $userRepository = new UserRepository();

            $username = '';

            if ($request->getMethod() === 'POST') {
                $body = $request->getParsedBody();
                
                // Retrieve username and password
                $username = $body['username'] ?? '';
                $password = $body['password'] ?? '';
                
                // Verify credentials
                $userEntity = $userRepository->getUserEntityByUserCredentials(
                    $username, 
                    $password, 
                    'authorization_code', 
                    $authRequest->getClient()
                );

                if ($userEntity instanceof \League\OAuth2\Server\Entities\UserEntityInterface) {
                    $authRequest->setUser($userEntity);
                    $authRequest->setAuthorizationApproved(true); // Auto-approve after login
                    
                    return $server->completeAuthorizationRequest($authRequest, $response);
                }
                
                $error = 'Invalid credentials';
            }
            
            // If GET or invalid login: show login/approval UI. 
            $html = '
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Authorize Access</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f5f5f7; margin: 0; }
                        .container { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 360px; }
                        h1 { font-size: 1.5rem; margin-top: 0; margin-bottom: 0.5rem; color: #1d1d1f; text-align: center; }
                        p.subtitle { text-align: center; color: #86868b; margin-top: 0; margin-bottom: 2rem; font-size: 0.9rem; }
                        input { width: 100%; padding: 0.8rem; margin-bottom: 1rem; border: 1px solid #d2d2d7; border-radius: 8px; box-sizing: border-box; font-size: 1rem; transition: border-color 0.2s; }
                        input:focus { border-color: #0071e3; outline: none; }
                        label { display: block; margin-bottom: 0.5rem; color: #1d1d1f; font-weight: 500; font-size: 0.9rem; }
                        button { width: 100%; padding: 0.8rem; background: #0071e3; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 500; transition: background 0.2s; }
                        button:hover { background: #0077ed; }
                        .error { background: #fee; color: #d00; padding: 0.75rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center; }
                        .client-name { font-weight: 600; color: #1d1d1f; }
                    </style>
                </head>
                <body>
                <div class="container">
                    <form method="post">
                        <h1>Sign In</h1>
                        <p class="subtitle">to continue to <span class="client-name">' . htmlspecialchars($authRequest->getClient()->getName()) . '</span></p>
                        
                        ' . (isset($error) ? '<div class="error">' . $error . '</div>' : '') . '
                        
                        <div>
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required autocomplete="username" value="' . htmlspecialchars($username) . '">
                        </div>
                        <div>
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required autocomplete="current-password">
                        </div>
                        <button type="submit">Sign In & Authorize</button>
                    </form>
                </div>
                </body>
                </html>
            ';
            
            $response->getBody()->write($html);
            return $response;


        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (\Exception $exception) {
            $response->getBody()->write($exception->getMessage());
            return $response->withStatus(500);
        }
    }
}
