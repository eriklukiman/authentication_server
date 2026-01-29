<?php

namespace Lukiman\AuthServer\Models;

use Lukiman\Cores\Model;

class Base extends Model {
    
    public function cleanData(mixed $data): mixed
    {
        return $data;
    }
}
