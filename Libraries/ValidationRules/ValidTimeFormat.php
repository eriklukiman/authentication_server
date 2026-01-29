<?php

namespace Lukiman\AuthServer\Libraries\ValidationRules;

use DateTime;

class ValidTimeFormat extends \Rakit\Validation\Rule {
    
    protected $message = ":attribute should be follow format HH:MM (24 hours)";

    public function check($value): bool {
        $d = DateTime::createFromFormat('H:i', $value);
        return $d && $d->format('H:i') === $value;
    }
}