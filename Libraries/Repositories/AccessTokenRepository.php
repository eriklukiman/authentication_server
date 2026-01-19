<?php

namespace Lukiman\AuthServer\Libraries\Repositories;

use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Lukiman\AuthServer\Libraries\Entities\AccessTokenEntity;
use Lukiman\AuthServer\Models\AccessToken;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        $accessToken->setUserIdentifier($userIdentifier);
        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $model = new AccessToken();
        $scopes = [];
        foreach ($accessTokenEntity->getScopes() as $scope) {
            $scopes[] = $scope->getIdentifier();
        }
        
        $model->create([
            'actkId' => $accessTokenEntity->getIdentifier(),
            'actkUserId' => $accessTokenEntity->getUserIdentifier(),
            'actkClientId' => $accessTokenEntity->getClient()->getIdentifier(),
            'actkScopes' => implode(' ', $scopes),
            'actkRevoked' => 0,
            'actkExpiresAt' => $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s')
        ]);
    }

    public function revokeAccessToken($tokenId)
    {
        $model = new AccessToken();
        $model->update($tokenId, ['actkRevoked' => 1]);
    }

    public function isAccessTokenRevoked($tokenId)
    {
        $model = new AccessToken();
        $record = $model->read($tokenId);
        if (!$record) {
            return true;
        }
        return (bool) $record['actkRevoked'];
    }
}
