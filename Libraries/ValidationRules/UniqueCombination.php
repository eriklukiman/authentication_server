<?php

namespace Lukiman\AuthServer\Libraries\ValidationRules;

use Lukiman\Cores\Database\Query\Select;
use Lukiman\AuthServer\Libraries\ValidationRules\Base;
use Lukiman\Cores\Database;

class UniqueCombination extends Base
{
    protected $message = 'The combination of :attribute and other fields already exists.';
    protected $fillableParams = ['table'];
    protected Select $query;

    public function __construct(?Select $query = null)
    {
        $this->query = $query ?? new Select();
    }

    public function getDb(): Database
    {
        return Database::getInstance();
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['table']);

        $params = $this->params;
        $table = array_shift($params);
        $additionalColumns = $params;

        $this->query->reset()->resetWhere()->table($table);
        $this->query->where($this->attribute->getKey(), $value);

        foreach ($additionalColumns as $column) {
            $additionalValue = $this->validator->getValue($column);
            $this->query->where($column, $additionalValue);
        }

        $recordCount = $this->query->execute($this->getDb())->count();

        return empty($recordCount);
    }
}