<?php

define('ROOT_PATH', dirname(__DIR__) . '/');
define('LUKIMAN_ROOT_PATH', ROOT_PATH);
chdir(ROOT_PATH);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/app.php';

use Lukiman\AuthServer\Models\Client;
use \Lukiman\Cores\Database\Query as Database_Query;

define('KEY_DIR', ROOT_PATH . '/keys');
define('EXPORT_DIR', ROOT_PATH . '/exports');

function promptInput(string $message): string
{
	if (function_exists('readline')) {
		$value = readline($message);
		return is_string($value) ? trim($value) : '';
	}

	echo $message;
	$value = fgets(STDIN);
	return $value === false ? '' : trim($value);
}

function normalizeFilenamePart(string $value): string
{
	$normalizedValue = $value;
	if (function_exists('iconv')) {
		$transliteratedValue = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
		if ($transliteratedValue !== false) {
			$normalizedValue = $transliteratedValue;
		}
	}

	$normalizedValue = preg_replace('/[^A-Za-z0-9._ -]+/', '-', $normalizedValue);
	$normalizedValue = preg_replace('/\s+/', ' ', $normalizedValue);
	$normalizedValue = preg_replace('/-+/', '-', $normalizedValue);
	$normalizedValue = trim($normalizedValue ?? '', " .-_");

	return $normalizedValue === '' ? 'client' : $normalizedValue;
}

function getAvailableClients(Client $clientModel): array
{
	$query = Database_Query::Select($clientModel->getTable())
		->order('clntName', 'ASC')
		->columns(['clntId', 'clntName'])
		->execute($clientModel->getDb());

	return (array) $query->fetchAll('array');
}

function selectClientId(Client $clientModel): string
{
	$clients = getAvailableClients($clientModel);
	if (empty($clients)) {
		throw new RuntimeException('No clients available to export.');
	}

	echo 'Available clients:' . PHP_EOL;
	foreach ($clients as $index => $client) {
		echo sprintf('%d. - %s: %s', $index + 1, $client['clntId'], $client['clntName']) . PHP_EOL;
	}

	$selection = promptInput('Select client number or client ID: ');
	if ($selection === '') {
		throw new RuntimeException('Client selection is required.');
	}

	if (ctype_digit($selection)) {
		$selectedIndex = (int) $selection - 1;
		if (!isset($clients[$selectedIndex])) {
			throw new RuntimeException('Selected client number is not valid.');
		}

		return $clients[$selectedIndex]['clntId'];
	}

	foreach ($clients as $client) {
		if ($client['clntId'] === $selection) {
			return $client['clntId'];
		}
	}

	throw new RuntimeException('Selected client ID is not valid.');
}

try {
	if (!class_exists('ZipArchive')) {
		throw new RuntimeException('ZipArchive extension is not available.');
	}

	$options = getopt('', ['client-id:', 'output::']);

	$clientModel = new Client();
	$clientId = $options['client-id'] ?? selectClientId($clientModel);
	$client = $clientModel->read($clientId);

	if (empty($client) || empty($client['clntId']) || empty($client['clntSecret'])) {
		throw new RuntimeException('Client credentials were not found for client ID: ' . $clientId);
	}

	$publicKeySource = KEY_DIR . '/' . $clientId . '/public.pem';
	if (!file_exists($publicKeySource)) {
		$fallbackPublicKeySource = KEY_DIR . '/' . $clientId . '/public.key';
		if (file_exists($fallbackPublicKeySource)) {
			$publicKeySource = $fallbackPublicKeySource;
		} else {
			throw new RuntimeException('Public key file was not found for client ID: ' . $clientId);
		}
	}

	$outputPath = $options['output'] ?? null;
	if (!$outputPath) {
		if (!file_exists(EXPORT_DIR) && !mkdir(EXPORT_DIR, 0744, true) && !is_dir(EXPORT_DIR)) {
			throw new RuntimeException('Unable to create export directory: ' . EXPORT_DIR);
		}

		$normalizedClientName = normalizeFilenamePart((string) ($client['clntName'] ?? 'client'));
		$outputPath = EXPORT_DIR . '/' . $clientId . ' - ' . $normalizedClientName . '.zip';
	} elseif (!str_starts_with($outputPath, '/')) {
		$outputPath = ROOT_PATH . '/' . ltrim($outputPath, '/');
	}

	$outputDirectory = dirname($outputPath);
	if (!file_exists($outputDirectory) && !mkdir($outputDirectory, 0744, true) && !is_dir($outputDirectory)) {
		throw new RuntimeException('Unable to create output directory: ' . $outputDirectory);
	}

	if (file_exists($outputPath) && !unlink($outputPath)) {
		throw new RuntimeException('Unable to replace existing zip file: ' . $outputPath);
	}

	$zip = new ZipArchive();
	if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
		throw new RuntimeException('Unable to create zip file: ' . $outputPath);
	}

	$credentials = 'Client ID: ' . $client['clntId'] . PHP_EOL . 'Client Secret: ' . $client['clntSecret'];

	if (!$zip->addFromString('credentials.txt', $credentials)) {
		$zip->close();
		throw new RuntimeException('Unable to add credentials.txt to zip archive.');
	}

	if (!$zip->addFile($publicKeySource, 'public.key')) {
		$zip->close();
		throw new RuntimeException('Unable to add public.key to zip archive.');
	}

	if (!$zip->close()) {
		throw new RuntimeException('Unable to finalize zip file: ' . $outputPath);
	}

	echo 'Exported client credentials to: ' . $outputPath . PHP_EOL;
} catch (Throwable $e) {
	echo 'Error exporting client credentials: ' . $e->getMessage() . PHP_EOL;
	exit(1);
}
