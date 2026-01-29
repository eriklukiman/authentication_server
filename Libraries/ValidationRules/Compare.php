<?php

namespace Lukiman\AuthServer\Libraries\ValidationRules;

use Rakit\Validation\Rule;

class Compare extends Rule {
  /**
   * Invalid message
   * 
   * @var string
   * */
  protected $message = ':attribute must be :operator :other.';

  /**
   * Required parameters
   *
   * @var string[]
   * */
  protected $fillableParams = [
    'other',
    'operator'
  ];

  public function fillParameters(array $params) : self
  {
    $this->setParameter('other', $params[0] ?? null);
    $this->setParameter('operator', $params[1] ?? '>');

    return $this;
  }

  public function check($value): bool
    {
      $this->requireParameters(['other', 'operator']);

      $otherField = $this->parameter('other');
      $operator = $this->parameter('operator');
      $otherValue = $this->validation->getValue($otherField);

      switch ($operator) {
        case '>':
          return $value > $otherValue;
        case '<':
          return $value < $otherValue;
        case '>=':
          return $value >= $otherValue;
        case '<=':
          return $value <= $otherValue;
        case '==':
          return $value == $otherValue;
        case '!=':
          return $value != $otherValue;
        default:
          throw new \InvalidArgumentException("Unsupported operator: $operator");
      }
    }
}
