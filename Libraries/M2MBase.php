<?php

namespace Lukiman\AuthServer\Libraries;

use App\Repository\NullAccessTokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Lukiman\Cores\Exception\PermissionDeniedException;
use Psr\Http\Message\ServerRequestInterface;

class M2MBase extends BaseApiModule {

    protected ServerRequestInterface $psrRequest;

    public function __construct()
    {
        parent::__construct();
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
        $this->psrRequest = $creator->fromGlobals();
    }

    public function beforeExecute(): void
    {
        try {

            parent::beforeExecute();

            $resourceServer = new ResourceServer(
                new NullAccessTokenRepository(),
                'file://' . __DIR__ . '/../public.key'
            );

            $this->psrRequest = $resourceServer
                    ->validateAuthenticatedRequest($this->psrRequest);
        } catch (OAuthServerException $e) {
            throw new PermissionDeniedException('OAuth Unauthorized: ' . $e->getMessage());
        }
        catch (\Exception $e) {
            throw new PermissionDeniedException('Unauthorized: ' . $e->getMessage());
        }
    }

}