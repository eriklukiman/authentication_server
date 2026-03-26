<?php
namespace Lukiman\AuthServer\Libraries;

use Lukiman\Cores\Controller\Json;
use Lukiman\Cores\Exception\Base as ExceptionBase;

class BaseApiModule extends Json{

    protected function getParsedBody(): array {
		$body = $this->request->getBody();
		if ($body === null) {
			return [];
		}
		$decoded = json_decode($body, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new ExceptionBase('Malformed JSON in request body: ' . json_last_error_msg());
		}
		return is_array($decoded) ? $decoded : [];
	}
}