<?php

namespace Lukiman\AuthServer\Libraries\ValidationRules;

use Rakit\Validation\Rule;

class Base extends Rule {

    public function check($value): bool
    {
        return true;
    }
}