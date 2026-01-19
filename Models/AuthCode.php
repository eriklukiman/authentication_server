<?php

namespace Lukiman\AuthServer\Models;

class AuthCode extends Base {
    protected String $table = 'auth_codes';
    protected String $prefix = 'aucd';
}
