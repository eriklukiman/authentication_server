<?php

namespace Lukiman\AuthServer\Libraries\Repositories;

use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Lukiman\AuthServer\Libraries\Entities\ScopeEntity;
use Lukiman\AuthServer\Models\Scope;

class ScopeRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier($identifier)
    {
        $scopeModel = new Scope();
        $record = $scopeModel->read($identifier);

        if (!$record) {
            return null;
        }

        $scope = new ScopeEntity();
        $scope->setIdentifier($identifier);
        return $scope;
    }

    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        // Example: just return requested scopes if they exist.
        // In a real app, you might filter specific scopes based on client/user.
        return $scopes;
    }
}
