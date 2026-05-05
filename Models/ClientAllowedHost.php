<?php

namespace Lukiman\AuthServer\Models;

class ClientAllowedHost extends Base {
    protected String $table = 'client_allowed_origins';
    protected String $prefix = 'clao';
}
