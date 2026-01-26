<?php

namespace App\Repository;

use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

class NullAccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function getNewToken(
        \League\OAuth2\Server\Entities\ClientEntityInterface $clientEntity,
        array $scopes,
        $userIdentifier = null
    ): AccessTokenEntityInterface {
        throw new \BadMethodCallException('Not implemented');
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        // Not used in resource server
    }

    public function revokeAccessToken($tokenId): void
    {
        // Not used in resource server
    }

    public function isAccessTokenRevoked($tokenId): bool
    {
        return false;
    }
}
