<?php

namespace Lukiman\AuthServer\Models;

class EventClient extends Base {
    protected String $table = 'event_client_association';
    protected String $primaryKey = 'evcaId';
}