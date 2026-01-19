<?php

namespace Lukiman\AuthServer\Libraries\Repositories;

use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use Lukiman\AuthServer\Libraries\Entities\AuthCodeEntity;
use Lukiman\AuthServer\Models\AuthCode;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $model = new AuthCode();
        $scopes = [];
        foreach ($authCodeEntity->getScopes() as $scope) {
            $scopes[] = $scope->getIdentifier();
        }

        $model->create([
            'aucdId' => $authCodeEntity->getIdentifier(),
            'aucdUserId' => $authCodeEntity->getUserIdentifier(),
            'aucdClientId' => $authCodeEntity->getClient()->getIdentifier(),
            'aucdScopes' => implode(' ', $scopes),
            'aucdRevoked' => 0,
            'aucdExpiresAt' => $authCodeEntity->getExpiryDateTime()->format('Y-m-d H:i:s')
        ]);
    }

    public function revokeAuthCode($codeId)
    {
        $model = new AuthCode();
        $model->update($codeId, ['aucdRevoked' => 1]);
    }

    public function isAuthCodeRevoked($codeId)
    {
        $model = new AuthCode();
        $record = $model->read($codeId);
        if (!$record) {
            return true;
        }
        return (bool) $record['aucdRevoked'];
    }
}
