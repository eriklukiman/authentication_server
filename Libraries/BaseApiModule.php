<?php
namespace Lukiman\AuthServer\Libraries;

use Lukiman\Cores\Controller\Json;
use Lukiman\Cores\Exception\Base as ExceptionBase;

class BaseApiModule extends Json{

	protected $filterParam = 'filters';
    protected $orderParam = 'orders';

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

	protected function buildFindFilter(array $get) : array {
		$retArray = [];

		if (!empty($get[$this->filterParam]) AND is_array($get[$this->filterParam])) {
			foreach ($get[$this->filterParam] as $key => $value) {
				$where = [
					'field'		=> $key,
					'operator'	=> 'LIKE',
					'value'		=> $value,
				];
				if (is_numeric($value)) {
					$where['operator'] = '=';
				} else if (is_array($value)) {
					$where['operator'] = 'IN';
				}
				$retArray[] = $where;
			}
		}

		return $retArray;
	}

	protected function buildSortFilter(array $get) : array {
		$retArray = [];

		if (!empty($get[$this->orderParam]) AND is_array($get[$this->orderParam])) {
			foreach ($get[$this->orderParam] as $key => $value) {
				$retArray[$key] = $value;
			}
		}

		return $retArray;
	}
}