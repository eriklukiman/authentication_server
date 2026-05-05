<?php

define('ROOT_PATH', dirname(__DIR__) . '/');
define('LUKIMAN_ROOT_PATH', ROOT_PATH);
chdir(ROOT_PATH);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/app.php';

use Lukiman\AuthServer\Models\AppClient;
use Lukiman\AuthServer\Models\Client;

define("KEY_DIR", ROOT_PATH . "/keys");

try {

    $options = getopt("", ["name:"]);

    $name = $options['name'] ?? null;

    // Check if name is provided
    if (!$name) {
        echo "Please provide name for the client using --name option." . PHP_EOL;
        exit(1);
    }

    // Generate a new client ID by randomize string with 40 characters length
    $clientId = bin2hex(random_bytes(20));
    $clientSecret = bin2hex(random_bytes(40));

    // Generate public and private key pair and save to the directory "keys/{ $clientId }" 
    //      with the name "private.pem" and "public.pem"

    // Make dir if not exists
    // Permission: 0755 (owner can read, write, execute; group and others can read and execute)

    // Make dir if not exists
    if (!file_exists(KEY_DIR)) {
        mkdir(KEY_DIR, 0755, true);
    }

    if (!file_exists(KEY_DIR. "/".$clientId) && !is_dir(KEY_DIR. "/".$clientId)) {
        mkdir(KEY_DIR. "/".$clientId, 0755, true);
    }

    $dir = realpath(KEY_DIR. "/".$clientId);
    echo "Generating keys for client ID: $clientId, Name: $name".PHP_EOL;
    echo "Keys will be saved in: $dir".PHP_EOL;

    $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );

    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privateKey);
    $publicKey = openssl_pkey_get_details($res);
    $publicKey = $publicKey["key"];

    // Save the private key to a file
    file_put_contents(KEY_DIR. "/".$clientId."/private.pem", $privateKey);

    // Save the public key to a file
    file_put_contents(KEY_DIR. "/".$clientId."/public.pem", $publicKey);

    $appClientModel = new AppClient();
    $appClientModel->insert([
        'apclId' => $clientId,
        'apclName' => $name,
    ]);

    $clientModel = new Client();
    $clientModel->insert([
        'clntId'        => $clientId,
        'clntSecret'    => $clientSecret,
        'clntName'      => $name,
        'clntRedirectUri' => 'wish-phototagging://oauth/callback',
        'clntIsConfidential' => 1,
    ]);

} catch (Throwable $e) {
    echo "Error generating keys: " . $e->getMessage() . PHP_EOL;
    exit(1);
}