<?php

namespace Lukiman\AuthServer\Libraries\Repositories;

use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use Lukiman\AuthServer\Libraries\Entities\RefreshTokenEntity;
use Lukiman\AuthServer\Models\RefreshToken;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $model = new RefreshToken();
        $model->create([
            'rftkId' => $refreshTokenEntity->getIdentifier(),
            'rftkAccessTokenId' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'rftkRevoked' => 0,
            'rftkExpiresAt' => $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s')
        ]);
    }

    public function revokeRefreshToken($tokenId)
    {
        $model = new RefreshToken();
        $model->update($tokenId, ['rftkRevoked' => 1]);
    }

    public function isRefreshTokenRevoked($tokenId)
    {
        $model = new RefreshToken();
        $record = $model->read($tokenId);
        if (!$record) {
            return true;
        }
        return (bool) $record['rftkRevoked'];
    }
}
