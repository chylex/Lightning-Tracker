<?php
declare(strict_types = 1);

namespace Validation;

use Validation\Types\DummyValidator;
use Validation\Types\IntValidator;
use Validation\Types\StringValidator;

final class FormValidator extends AbstractValidator{
  private array $data;
  
  public function __construct(array $data){
    $this->data = $data;
  }
  
  public function str(string $field, ?string $label = null): StringValidator{
    $validator = new StringValidator($field, $this->data[$field], $label);
    $this->validators[] = $validator;
    return $validator;
  }
  
  public function int(string $field, ?string $label = null): IntValidator{
    $validator = new IntValidator($field, (int)$this->data[$field], $label);
    $this->validators[] = $validator;
    return $validator;
  }
  
  public function bool(string $field, ?string $label = null): DummyValidator{
    $validator = new DummyValidator($field, (bool)($this->data[$field] ?? false), $label);
    $this->validators[] = $validator;
    return $validator;
  }
}

?>
