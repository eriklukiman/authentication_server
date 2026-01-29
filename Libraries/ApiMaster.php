<?php

namespace Lukiman\AuthServer\Libraries;

use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Lukiman\AuthServer\Libraries\Repositories\AccessTokenRepository;
use Psr\Http\Message\ServerRequestInterface;
use \Lukiman\Cores\Database\Query as Database_Query;
use \Lukiman\Cores\Exception\Base as ExceptionBase;
use Lukiman\AuthServer\Models\Base as BaseModel;

class ApiMaster extends BaseApiModule {

	protected array $fieldValidations;
	protected bool $hideFieldPrefix = false;
	protected BaseModel $model;
	protected Validation $validation;
	protected $files;

	protected String $orderParam = 'orders';
	protected String $filterParam = 'filters';

    protected ServerRequestInterface $psrRequest;
    protected bool $useTimeStamps = false;

    public function __construct(?BaseModel $model = null)
    {
        parent::__construct();
        
        // Convert framework request to PSR-7 request
        $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
        $this->psrRequest = $creator->fromGlobals();
        if ($model !== null) {
            $this->model = $model;
        }
        $this->validation = new Validation();
    }

    public function do_Index($param) {
		$method = strtolower($this->request->getmethod());

		if ($method == 'get') {
			return $this->do_Get($param);
		} else if ($method == 'post') {
			return $this->do_Insert($param);
		} else if (($method == 'put') OR ($method == 'patch')) {
			return $this->do_Update($param);
		} else if ($method == 'delete') {
			return $this->do_Delete($param);
		} else {
			throw new ExceptionBase('No Action defined!', 404);
		}
	}

    public function do_Get($param) {
		return $this->getData($param);
	}

    public function do_Insert($param) {
		$id = $this->addData($param);
		if ($id < 0) {
			$this->prepareErrorReturn();
			return [];
		} else return ['id' => $id];
	}

	public function do_Update($param) {
		$affected_rows = $this->updateData($param);
		if ($affected_rows < 0) {
			$this->prepareErrorReturn();
			return [];
		}
		return ['affected_rows' => $affected_rows];
	}

	public function do_Delete($param) {
		return ['affected_rows' => $this->deleteData($param)];
	}

    protected function filterPrefix(String $field, String|null $prefix = null) : String {
		if ($this->hideFieldPrefix) {
			$prefix = $prefix ?? $this->model->getPrefix();
			$prefix = $prefix ?? $this->model->getPrefix();
			if (substr($field, 0, strlen($prefix)) == $prefix) {
				return lcfirst(substr($field, strlen($prefix)));
			}
		}
		return $field;
	}
	
	protected function filterPrefixes(array $fields, String|null $prefix = null) : array {
		$ret = [];
		foreach ($fields as $field => $v) $ret[$this->filterPrefix($field, $prefix)] = $v;
		return $ret;
	}

	protected function getFieldValidations() : array {
		return $this->fieldValidations;
	}

	protected function setFieldValidations(array $fieldValidations) : self {
		$this->fieldValidations = $fieldValidations;
		return $this;
	}

	protected function getData($param) : array {
		$q = Database_Query::Grid($this->model->getTable());
		if (!empty($param) AND !empty($param[0])) {
			$q->where($this->model->getPrimaryKey(), $param[0]);
		}

		$q->setRequest($this->request);

		$get = $this->request->getGetVars();
		
		$filters = $this->buildFindFilter($get);
		if (!empty($filters)) {
			foreach ($filters as $key => $value) {
				if ($value['operator'] == 'LIKE') {
					$q->where($value['field'] . ' ' . $value['operator'] . ' ' .  '"%' . $value['value'] . '%"');
				} else if ($value['operator'] == 'IN') {
					$q->where($value['field'], $value['value'], $value['operator']);
				} else {
					$q->where($value['field'], $value['value'], $value['operator']);
				}
			}
		}

		$orders = $this->buildSortFilter($get);
		if (!empty($orders)) {
			foreach ($orders as $key => $value) {
				$q->order($key, $value);
			}
		}


		$data = $q->execute($this->model->getDb());
		$ret = array('data' => []);
		while ($v = $data->next()) {
			$v = (array) $v;
			$ret['data'][] = $v;
		}
		$ret['pagination'] = $q->getGridInfo();

		return $ret;
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

    protected function addData($param) : int {
		$data = $this->getParsedBody();

		if (empty($data)) {
			throw new ExceptionBase('No Data to be added!', 452);
		}

        if ($this->useTimeStamps) {
		    $data[$this->model->getPrefix() . 'CreatedUserId'] = $this->getId();
        }

		$data = $this->cleanData($data);
		if (!$this->validate($data)) return -1;

		return $this->model->insert($data);
	}

	protected function updateData($param) : int {
		if (empty($param) OR empty($param[0])) {
			throw new ExceptionBase('No Data ID supplied!', 451);
		}

		$data = $this->getParsedBody();

		if (empty($data)) {
			throw new ExceptionBase('No Data to update!', 452);
		}

        if ($this->useTimeStamps) {
		    $data[$this->model->getPrefix() . 'UpdatedUserId'] = $this->getId();
        }

		$optWhere = [];

		$data = $this->cleanData($data);
		if (!$this->validate($data)) return -1;

		return $this->model->update($param[0], $data, $optWhere);
	}

	protected function deleteData($param) : int {
		if (empty($param) OR empty($param[0])) {
			throw new ExceptionBase('No Data ID supplied!', 451);
		}

		$optWhere = [];

		return $this->model->delete($param[0], $optWhere);
	}

	protected function setValidationRules(array $rules) : self {
		$this->validation->setRules($rules);
		return $this;
	}

	protected function hideFieldPrefix() : void {
		$this->hideFieldPrefix = true;
	}

	protected function validate(array $inputs) : bool {
		return $this->validation->validate($inputs);
	}

    protected function prepareErrorReturn() : void {
		if (!empty($this->validation->errors())) {
			$this->setError(1);
			$this->setErrorCode(401);
			$this->setErrorMessage($this->validation->errorsToArray());
		}
	}


    protected function beforeExecute(): void {
        parent::beforeExecute(); // Best practice to call parent

        $accessTokenRepository = new AccessTokenRepository();
        $publicKeyPath = 'file://' . __DIR__ . '/../public.key';

        $server = new ResourceServer(
            $accessTokenRepository,
            $publicKeyPath
        );

        try {
            $this->psrRequest = $server->validateAuthenticatedRequest($this->psrRequest);
        } catch (OAuthServerException $exception) {
            // Handle validation error
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode([
                'status' => [
                    'error' => true,
                    'errorCode' => 401,
                    'message' => $exception->getMessage(),
                    'hint' => $exception->getHint()
                ]
            ]);
            exit;
        } catch (\Exception $exception) {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');
            echo json_encode([
                'status' => [
                    'error' => true,
                    'errorCode' => 500,
                    'message' => $exception->getMessage()
                ]
            ]);
            exit;
        }
    }

    protected function getId() : int {
        $attributes = $this->psrRequest->getAttributes();
        return isset($attributes['oauth_user_id']) ? (int)$attributes['oauth_user_id'] : 0;
    }

    protected function getScopes() : array {
        $attributes = $this->psrRequest->getAttributes();
        return isset($attributes['oauth_scopes']) ? (array)$attributes['oauth_scopes'] : [];
    }

    protected function camelCaseToString($string) : String {
		$pieces = preg_split('/(?=[A-Z])/',$string);
		$word = implode(" ", $pieces);
		return ucwords(trim($word));
	}

    protected function cleanData(array $data) : array {
		return $data = $this->model->cleanData($data);
	}

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