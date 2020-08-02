<?php
declare(strict_types = 1);

namespace Validation;

abstract class AbstractFieldValidator{
  protected string $field;
  protected string $label;
  private ?string $error;
  
  public function __construct(string $field, ?string $label){
    $this->field = $field;
    $this->label = $label ?? $field;
    $this->error = null;
  }
  
  /**
   * @return mixed
   */
  protected abstract function getValue();
  
  public function isTrue(callable $test, string $message): self{
    if ($this->noError() && !$test($this->getValue())){
      $this->fail($message);
    }
    
    return $this;
  }
  
  protected function fail(string $error): void{
    if ($this->error === null){
      $this->error = $error;
    }
  }
  
  public function noError(): bool{
    return $this->error === null;
  }
  
  public function getError(): ?InvalidField{
    return $this->error === null ? null : new InvalidField($this->field, $this->error);
  }
}

?>
