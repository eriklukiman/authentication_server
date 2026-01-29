<?php
namespace Lukiman\AuthServer\Libraries;

use Lukiman\AuthServer\Libraries\ValidationRules\Compare;
use Lukiman\AuthServer\Libraries\ValidationRules\RecordExists;
use Lukiman\AuthServer\Libraries\ValidationRules\RecordExistsExcept;
use Lukiman\AuthServer\Libraries\ValidationRules\RecordNotExists;
use Lukiman\AuthServer\Libraries\ValidationRules\RecordNotExistsExcept;
use Lukiman\AuthServer\Libraries\ValidationRules\UniqueCombination;
use Lukiman\AuthServer\Libraries\ValidationRules\UniqueCombinationExcept;
use Lukiman\AuthServer\Libraries\ValidationRules\ValidTimeFormat;
use Rakit\Validation\Rule;
use Rakit\Validation\Validation as rValidation;
use Rakit\Validation\Validator;

class Validation {
	protected Validator $validator;
	protected rValidation $validation;
	protected array $rules;
	protected array $aliases;
	protected array $customValidators = [
		'compare' => Compare::class,
		'exists' => RecordExists::class,
		'notExists'=> RecordNotExists::class,
		'notExistsExcept'=> RecordNotExistsExcept::class,
		'existsExcept' => RecordExistsExcept::class,
		'uniqueCombination' => UniqueCombination::class,
		'uniqueCombinationExcept' => UniqueCombinationExcept::class,
		'time' => ValidTimeFormat::class,
	];

	public function __construct(array $rules = []) {
		$this->validator = new Validator();
		$this->setRules($rules);
		$this->addCustomValidators();
	}

	public function setRules(array $complexRules = []) : void {
		if (empty($complexRules)) {
			$this->rules = [];
			$this->aliases = [];
		}

		$rules = [];
		$aliases = [];
		foreach ($complexRules as $key => $val) {
			if (is_array($val)) {
				if (!empty($val['rule'])) $rules[$key] = $val['rule'];
				if (!empty($val['name'])) $aliases[$key] = $val['name'];
			} else {
				$rules[$key] = $val;
			}
		}
		$this->rules = $rules;
		$this->aliases = $aliases;
	}

	/**
	 * Add Custom Validators
	 *
	 * @return void
	 * */
	public function addCustomValidators(): void {
		foreach ($this->customValidators as $ruleName => $validator) {
			if (is_string($validator)) {
				$validator = new $validator();
			}

			if ($validator instanceof Rule) {
				$this->validator->addValidator($ruleName, $validator);
			}
		}
	}

	public function validate(array $inputs) : bool {
		$this->validation = $this->validator->make($inputs, $this->rules);
		$this->validation->setAliases($this->aliases);
		$this->validation->validate();
		if ($this->validation->fails()) return false;
		return true;
	}

	public function errors() : array {
		return $this->validation->errors()->all();
	}

	public function errorsToArray() : array {
		return $this->validation->errors()->toArray();
	}
}
