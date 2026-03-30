<?php

namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\BaseApiModule;
use Lukiman\AuthServer\Libraries\Logger;
use Lukiman\AuthServer\Models\AppClient;
use Lukiman\Cores\Exception\AuthorizationRejectedException;
use Lukiman\Cores\Exception\NotFoundException;
use Lukiman\Cores\Exception\ServerErrorException;
use Lukiman\Cores\Exception\ValidationErrorException;

class Check_License extends BaseApiModule {
    public function __construct() {
        parent::__construct();
    }

    public function do_Index() {
        $postData = $this->getParsedBody();
        Logger::info('Received license check request: ' . json_encode($postData));
        $clientId = $postData['client_id'] ?? null;
        $machineId = $postData['machine_id'] ?? null;
        $data = $postData['data'] ?? null;

        // validate input
        if (empty($clientId)) {
            Logger::error('client_id is required');
            throw new ValidationErrorException('client_id is required', 400);
        }

        if (empty($machineId)) {
            Logger::error('machine_id is required');
            throw new ValidationErrorException('machine_id is required', 400);
        }

        // Check if clientId is exists
        $appClientModel = new AppClient();
        $clientData = $appClientModel->read($clientId);
        if (empty($clientData) || empty($clientData['apclId'])) {
            Logger::error('Invalid client_id: ' . $clientId);
            throw new ValidationErrorException('Invalid client_id', 400);
        }

        // Check if client is expired
        if (!empty($clientData['apclExpiredTime']) || $clientData['apclIsExpired'] == 0) {
            $expiredTime = strtotime($clientData['apclExpiredTime']);
            if (time() > $expiredTime) {
                Logger::info('Client is expired: ' . $clientId);
                $appClientModel->update($clientId, [
                    'apclIsExpired' => 1
                ]);
                throw new AuthorizationRejectedException('Client is expired', 400);
            }
        } else if ($clientData['apclIsExpired'] == 1) {
            Logger::info('Client is expired: ' . $clientId);
            throw new AuthorizationRejectedException('Client is expired', 400);
        }

        // Check if machineId is null, then insert and return valid
        if (empty($clientData['apclMachineId'])) {
            Logger::info('Machine ID is null, updating with new machine ID: ' . $machineId);

            $appClientModel->update($clientId, [
                'apclMachineId' => $machineId
            ]);
            $clientData['apclMachineId'] = $machineId;
        } else if ($clientData['apclMachineId'] !== $machineId) {
            Logger::error('Invalid machine ID: ' . $machineId);

            throw new ValidationErrorException('Invalid key', 400);
        }

        // Validate payload data encrypted with public key,
        // then decrypt with private key and check timestamp is not expired (5 minutes)
        // If valid, return valid, if not valid, return invalid
        $privateKeyPath = ROOT_PATH . "/keys/" . $clientId . "/private.pem";

        if (!file_exists($privateKeyPath)) {
            Logger::error('Keys not found for the client: ' . $clientId);
            throw new NotFoundException('Keys not found for the client', 400);
        }

        if (empty($data)) {
            Logger::error('data is required');
            throw new ValidationErrorException('data is required', 400);
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if (openssl_private_decrypt(base64_decode($data), $decryptedData, $privateKey)) {
            $decodedData = json_decode($decryptedData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Logger::error('Malformed JSON in decrypted data: ' . json_last_error_msg());
                throw new ValidationErrorException('Malformed JSON in decrypted data: ' . json_last_error_msg(), 400);
            }
            $timestamp = $decodedData['timestamp'] ?? null;
            if (empty($timestamp)) {
                Logger::error('timestamp is required in decrypted data');

                throw new AuthorizationRejectedException('timestamp is required in decrypted data', 400);
            }
            if (abs(time() - $timestamp) > 300) { // 5 minutes
                Logger::error('Timestamp is expired');
                throw new AuthorizationRejectedException('Timestamp is expired', 400);
            }
            openssl_private_encrypt("ACK", $encryptedResponse, $privateKey);
            return [
                'data' => base64_encode($encryptedResponse)
            ];
        }
        throw new ServerErrorException('License is invalid', 500);
    }
}