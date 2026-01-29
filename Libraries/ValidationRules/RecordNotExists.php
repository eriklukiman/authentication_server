<?php

namespace Lukiman\AuthServer\Libraries\ValidationRules;

use Lukiman\Cores\Database\Query\Select;
use Lukiman\AuthServer\Libraries\ValidationRules\Base;
use Lukiman\Cores\Database;

class RecordNotExists extends Base {
  /**
   * Invalid message
   * 
   * @var string
   * */
  protected $message = 'The ":value" already exist';

  /**
   * Required parameters
   *
   * @var string[]
   * */
  protected $fillableParams = [
    'table',
    'column'
  ];

  /**
   * Select Query
   * 
   * @var \Lukiman\Cores\Database\Query\Select
   * */
  protected Select $query;

  /**
   * @param \Lukiman\Cores\Database\Query\Select|null $query
   * */
  public function __construct(?Select $query = null) {
    $this->query = $query ?? new Select();
  }

  /**
   * @return \Lukiman\Cores\Database
   * */
  public function getDb(): Database {
    return Database::getInstance();
  }

  public function check(mixed $value): bool {
    // make sure required parameters exists
    $this->requireParameters(['table', 'column']);
    // need to reset the query 
    $this->query->reset()->resetWhere();

    $isRecordExist = $this->query->table($this->parameter('table'))
      ->where($this->parameter('column'), $value)
      ->execute($this->getDb())->count();

    return empty($isRecordExist);
  }
}
