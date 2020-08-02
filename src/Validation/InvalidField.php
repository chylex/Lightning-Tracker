<?php
declare(strict_types = 1);

namespace Validation;

final class InvalidField{
  private string $field;
  private string $message;
  
  public function __construct($field, $message){
    $this->field = $field;
    $this->message = $message;
  }
  
  public function getField(): string{
    return $this->field;
  }
  
  public function getMessage(): string{
    return $this->message;
  }
}

?>
