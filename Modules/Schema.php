<?php
namespace Lukiman\AuthServer\Modules;

use Lukiman\AuthServer\Libraries\Schema as SchemaHandler;

class Schema extends \Lukiman\AuthServer\Libraries\BaseController {

	private SchemaHandler $instance;

	public function __construct() {
		parent::__construct();
		$this->instance = new SchemaHandler();
	}
	
	public function do_History($param) {
		return $this->instance->get_History($param);
	}

	public function do_Update($param) {
		return $this->instance->get_Update($param);
	}
}