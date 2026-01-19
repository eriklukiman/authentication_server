<?php

namespace Lukiman\AuthServer\Models;

class AccessToken extends Base {
    protected String $table = 'access_tokens';
    protected String $prefix = 'actk';
}
