<?php
declare(strict_types = 1);

namespace Pages\Components\Forms;

abstract class AbstractFormField implements IFormField{
  private string $name;
  private array $errors = [];
  
  protected string $value = "";
  protected bool $disabled = false;
  
  public function __construct(string $name){
    $this->name = $name;
  }
  
  public function value(string $value): self{
    $this->value = $value;
    return $this;
  }
  
  public function disabled(): self{
    $this->disabled = true;
    return $this;
  }
  
  public function getName(): string{
    return $this->name;
  }
  
  public function addError(string $message): void{
    $this->errors[] = $message;
  }
  
  public function acceptsMissingField(): bool{
    return false;
  }
  
  protected final function echoErrors(): void{
    foreach($this->errors as $error){
      echo '<p class="message error">'.$error.'</p>';
    }
  }
}

?>
