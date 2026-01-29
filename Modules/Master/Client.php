<?php

namespace Lukiman\AuthServer\Modules\Master;

use Lukiman\AuthServer\Libraries\ApiMaster;
use Lukiman\AuthServer\Models\Client as ClientModel;
use Lukiman\Cores\Exception\NotFoundException;
use Lukiman\Cores\Exception\ValidationErrorException;

class Client extends ApiMaster {

    private array $validationRules = [];
    public function __construct()
    {
        parent::__construct(new ClientModel());
        $this->validationRules = [
            'clntId'            => 'required|notExists:clients,clntId|min:10|max:80',
            'clntSecret'        => 'required|min:20|max:255',
            'clntName'          => 'required|min:1|max:255',
            'clntRedirectUri'   => 'required|url|max:2000',
        ];
        $this->setValidationRules($this->validationRules);
    }

    public function updateData($param): int
    {
        if (empty($param) OR empty($param[0])) {
			throw new ValidationErrorException('No Data ID supplied!', 451);
		}
        $currentData = $this->model->read($param[0]);
        if (empty($currentData) || empty($currentData['clntId'])) {
            throw new NotFoundException('Data not found!', 453);
        }

        $this->validationRules['clntId'] = 'required|in:'.$currentData['clntId'].'|exists:clients,clntId';
        $this->setValidationRules($this->validationRules);
        
        return parent::updateData($param);
    }

    public function deleteData($param): int
    {
        if (empty($param) OR empty($param[0])) {
			throw new ValidationErrorException('No Data ID supplied!', 451);
		}
        $currentData = $this->model->read($param[0]);
        if (empty($currentData) || empty($currentData['clntId'])) {
            throw new NotFoundException('Data not found!', 453);
        }

        return parent::deleteData($param);
    }
}