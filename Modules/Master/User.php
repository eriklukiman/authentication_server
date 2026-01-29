<?php

namespace Lukiman\AuthServer\Modules\Master;

use Lukiman\AuthServer\Libraries\ApiMaster;
use Lukiman\AuthServer\Models\User as UserModel;
use Lukiman\Cores\Exception\ValidationErrorException;

class User extends ApiMaster {

    private array $validationRules = [];

    public function __construct()
    {
        parent::__construct(new UserModel());
        $this->validationRules = [
            'userUsername'  => 'required|notExists:users,userUsername|min:5|max:100',
            'userPassword'  => 'required|min:8|max:255',
        ];
        $this->setValidationRules($this->validationRules);
    }

    public function getData($param): array
    {
        $data = parent::getData($param);
        $data['data'] = array_map(function ($row) {
            unset($row['userPassword']);
            return $row;
        }, $data['data']);
        return $data;
    }

    public function addData($param): int
    {
        $data = $this->getParsedBody();
        if (!$this->validate($data)) return -1;
        $data['userPassword'] = password_hash($data['userPassword'], PASSWORD_BCRYPT);
        return $this->model->insert($data);
    }

    public function updateData($param): int
    {
        if (empty($param) OR empty($param[0])) {
            throw new ValidationErrorException('No Data ID supplied!', 451);
        }
        $currentData = $this->model->read($param[0]);
        if (empty($currentData) || empty($currentData['userId'])) {
            throw new ValidationErrorException('Data not found!', 453);
        }

        $data = $this->getParsedBody();
        $this->validationRules['userUsername'] = 'required|notExistsExcept:users,userUsername,userUsername,'.$currentData['userUsername'].'|min:5|max:100';
        $this->validationRules['userPassword'] = 'nullable|min:8|max:255';
        $this->setValidationRules($this->validationRules);
        if (!$this->validate($data)) return -1;
        if (!empty($data['userPassword'])) {
            $data['userPassword'] = password_hash($data['userPassword'], PASSWORD_BCRYPT);
        } else {
            unset($data['userPassword']);
        }

        return $this->model->update($param[0], $data);
    }

    public function deleteData($param): int
    {
        if (empty($param) OR empty($param[0])) {
            throw new ValidationErrorException('No Data ID supplied!', 451);
        }
        $currentData = $this->model->read($param[0]);
        if (empty($currentData) || empty($currentData['userId'])) {
            throw new ValidationErrorException('Data not found!', 453);
        }

        return parent::deleteData($param);
    }
}