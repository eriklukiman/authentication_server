<?php

namespace Lukiman\AuthServer\Models;

use Lukiman\Cores\Database\Query\Select;
use Lukiman\Cores\Model;

class Base extends Model {
    
    public function cleanData(mixed $data): mixed
    {
        return $data;
    }

    public function newQuery(): Select {
        return new Select(
            $this->getTable(),
            $this->getDb()
        );
    }
}
