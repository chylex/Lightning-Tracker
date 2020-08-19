<?php
declare(strict_types = 1);

namespace Validation\Types;

use Validation\AbstractFieldValidator;

class IntValidator extends AbstractFieldValidator{
  private int $value;
  
  public function __construct(string $field, int $value, ?string $label){
    parent::__construct($field, $label);
    $this->value = $value;
  }
  
  public function val(): int{
    return $this->value;
  }
  
  public function min(int $min, ?string $message = null): self{
    if ($this->noError() && $this->value < $min){
      $this->fail($message ?? $this->label.' must be at least '.$min.'.');
    }
    
    return $this;
  }
  
  public function max(int $max, ?string $message = null): self{
    if ($this->noError() && $this->value > $max){
      $this->fail($message ?? $this->label.' must be at most '.$max.'.');
    }
    
    return $this;
  }
}

?>
