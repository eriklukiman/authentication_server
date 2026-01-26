<?php

namespace Lukiman\AuthServer\Libraries\Repositories;

use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Lukiman\AuthServer\Libraries\Entities\UserEntity;
use Lukiman\AuthServer\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $userModel = new User();
        $rows = $userModel->getData(['userUsername' => $username]);

        if ($rows->count() == 0) {
            return null;
        }

        $record = $rows->next('array');

        if (!$record) {
            return null;
        }

        if (password_verify($password, $record['userPassword'])) {
            $user = new UserEntity();
            $user->setIdentifier($record['userId']);
            return $user;
        }

        return null;
    }
}
