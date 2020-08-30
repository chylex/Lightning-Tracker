<?php
declare(strict_types = 1);

namespace Validation\Types;

use Validation\AbstractFieldValidator;

class StringValidator extends AbstractFieldValidator{
  private string $value;
  
  public function __construct(string $field, string $value, ?string $label){
    parent::__construct($field, $label);
    $this->value = $value;
  }
  
  public function val(): string{
    return $this->value;
  }
  
  public function notEmpty(?string $message = null): self{
    if ($this->noError() && empty($this->value)){
      $this->fail($message ?? $this->label.' must not be empty.');
    }
    
    return $this;
  }
  
  public function contains(string $needle, ?string $message = null): self{
    if ($this->noError() && mb_strpos($this->value, $needle) === false){
      $this->fail($message ?? $this->label.' must contain \''.$needle.'\'.');
    }
    
    return $this;
  }
  
  public function notContains(string $needle, ?string $message = null): self{
    if ($this->noError() && mb_strpos($this->value, $needle) !== false){
      $this->fail($message ?? $this->label.' must not contain \''.$needle.'\'.');
    }
    
    return $this;
  }
  
  public function minLength(int $minLength, ?string $message = null): self{
    if ($this->noError() && strlen($this->value) < $minLength){
      $this->fail($message ?? $this->label.' must be at least '.$minLength.' characters long.');
    }
    
    return $this;
  }
  
  public function maxLength(int $maxLength, ?string $message = null): self{
    if ($this->noError() && strlen($this->value) > $maxLength){
      $this->fail($message ?? $this->label.' must be at most '.$maxLength.' characters long.');
    }
    
    return $this;
  }
}

?>
