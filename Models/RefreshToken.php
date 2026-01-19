<?php

namespace Lukiman\AuthServer\Models;

class RefreshToken extends Base {
    protected String $table = 'refresh_tokens';
    protected String $prefix = 'rftk';
}
