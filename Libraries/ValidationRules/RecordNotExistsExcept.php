<?php

namespace Lukiman\AuthServer\Libraries\ValidationRules;

use Lukiman\Cores\Database\Query\Select;
use Lukiman\AuthServer\Libraries\ValidationRules\Base;
use Lukiman\Cores\Database;

class RecordNotExistsExcept extends Base
{
    protected $message = 'The ":value" already exists.';

    protected $fillableParams = [
        'table',
        'column',
        'exceptColumn',
        'exceptValue'
    ];

    protected Select $query;

    public function __construct(?Select $query = null)
    {
        $this->query = $query ?? new Select();
    }

    protected function getDb(): Database
    {
        return Database::getInstance();
    }

    public function check(mixed $value): bool
    {
        $this->requireParameters(['table', 'column']);

        $params = $this->params;

        $table        = array_shift($params);
        $targetColumn = array_shift($params);

        $ignoreColumn = null;
        $ignoreValue  = null;

        if (count($params) >= 2) {
            $ignoreColumn = array_shift($params);
            $ignoreValue  = array_shift($params);
        }

        $this->query
            ->reset()
            ->resetWhere()
            ->table($table)
            ->where($targetColumn, $value);

        if ($ignoreColumn !== null && $ignoreValue !== null) {
            $this->query->where(
                $ignoreColumn,
                $ignoreValue,
                '<>'
            );
        }

        return $this->query
            ->execute($this->getDb())
            ->count() === 0;
    }
}