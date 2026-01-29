<?php
namespace Lukiman\AuthServer\Models;

use Lukiman\AuthServer\Models\Base;

class Schema extends Base {
	protected string $table = 'schema_sync';
	protected string $prefix = 'schm';
	protected string $primaryKey = '"schmId"';
}
