<?php

namespace Lukiman\AuthServer\Libraries\Repositories;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Lukiman\AuthServer\Libraries\Entities\ClientEntity;
use Lukiman\AuthServer\Models\Client;

class ClientRepository implements ClientRepositoryInterface
{
    public function getClientEntity($clientIdentifier)
    {
        $clientModel = new Client();
        $record = $clientModel->read($clientIdentifier);

        if (!$record) {
            return null;
        }

        $client = new ClientEntity();
        $client->setIdentifier($clientIdentifier);
        $client->setName($record['clntName']);
        $client->setRedirectUri($record['clntRedirectUri']);
        $client->setConfidential($record['clntIsConfidential']);

        return $client;
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $clientModel = new Client();
        $record = $clientModel->read($clientIdentifier);

        if (!$record) {
            return false;
        }

        if ($record['clntIsConfidential']) {
            // For production, use password_verify with hashed secrets.
            // For this demo/seed, we compare plaintext.
            if ($clientSecret !== $record['clntSecret']) {
                return false;
            }
        }

        return true;
    }
}
