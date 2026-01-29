<?php

namespace Lukiman\AuthServer\Libraries\ValidationRules;

use Lukiman\Cores\Database\Query\Select;
use Lukiman\AuthServer\Libraries\ValidationRules\Base;
use Lukiman\Cores\Database;

class UniqueCombinationExcept extends Base
{
    protected $message = 'The combination of :attribute and other fields must be unique.';
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

        $ignoreColumn = null;
        $ignoreValue = null;
        
        // last 2 params are value and column, usually used for ignoring pk for update
        if (count($params) >= 2) {
            $ignoreValue = array_pop($params);
            $ignoreColumn = array_pop($params);
        }

        $additionalColumns = $params;

        $this->query->reset()->resetWhere()->table($table);
        $this->query->where($this->attribute->getKey(), $value);

        foreach ($additionalColumns as $column) {
            $additionalValue = $this->validator->getValue($column);
            $this->query->where($column, $additionalValue);
        }

        if ($ignoreColumn && $ignoreValue) {
            $this->query->where($ignoreColumn, '<>', $ignoreValue);
        }

        $recordCount = $this->query->execute($this->getDb())->count();

        return empty($recordCount);
    }
}