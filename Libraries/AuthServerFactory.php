<?php

namespace Lukiman\AuthServer\Libraries;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Lukiman\AuthServer\Libraries\Repositories\ClientRepository;
use Lukiman\AuthServer\Libraries\Repositories\AccessTokenRepository;
use Lukiman\AuthServer\Libraries\Repositories\ScopeRepository;
use Lukiman\AuthServer\Libraries\Repositories\UserRepository;
use Lukiman\AuthServer\Libraries\Repositories\RefreshTokenRepository;
use Lukiman\AuthServer\Libraries\Repositories\AuthCodeRepository;

class AuthServerFactory
{
    public static function create()
    {
        $clientRepository = new ClientRepository();
        $scopeRepository = new ScopeRepository();
        $accessTokenRepository = new AccessTokenRepository();
        $userRepository = new UserRepository();
        $refreshTokenRepository = new RefreshTokenRepository();
        $authCodeRepository = new AuthCodeRepository();

        // Fix: Libraries is one level deep from root.
        // __DIR__ = .../Libraries
        // __DIR__ . '/../private.key' = .../private.key
        $privateKey = 'file://' . __DIR__ . '/../private.key';
        
        // Valid 32-byte key (base64 encoded or raw string of 32 bytes)
        // Defuse might want specific format, usually standard string is fine if it handles it. 
        // Or we pass a Key object.
        // League OAuth2 expects string(32) or Defuse Key.
        // Let's use the one we generated.
        $encryptionKey = 'DCmf5P8Me/bZb+otV5yGpCxCpX2b76JTTve2bsBIXSE='; 

        // Setup the authorization server
        $server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $privateKey,
            $encryptionKey
        );

        // Enable Client Credentials Grant
        $server->enableGrantType(
            new ClientCredentialsGrant(),
            new \DateInterval('PT1H') // access token TTL
        );

        // Enable Password Grant
        $grant = new PasswordGrant(
            $userRepository,
            $refreshTokenRepository
        );
        $grant->setRefreshTokenTTL(new \DateInterval('P1M')); 
        $server->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access token TTL
        );

        // Enable Auth Code Grant
        $grant = new AuthCodeGrant(
            $authCodeRepository,
            $refreshTokenRepository,
            new \DateInterval('PT10M') // auth code TTL
        );
        $grant->setRefreshTokenTTL(new \DateInterval('P1M'));
        $server->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access token TTL
        );

        // Enable Refresh Token Grant
        $grant = new RefreshTokenGrant(
            $refreshTokenRepository
        );
        $grant->setRefreshTokenTTL(new \DateInterval('P1M')); 
        $server->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access token TTL
        );

        return $server;
    }
}
