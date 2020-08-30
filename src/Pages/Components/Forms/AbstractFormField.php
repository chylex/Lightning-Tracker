<?php
declare(strict_types = 1);

namespace Pages\Components\Forms;

abstract class AbstractFormField implements IFormField{
  private string $id;
  private string $name;
  private array $errors = [];
  
  protected string $value = '';
  protected bool $disabled = false;
  
  public function __construct(string $id, string $name){
    $this->id = $id;
    $this->name = $name;
  }
  
  protected function getId(): string{
    return $this->id;
  }
  
  protected function getName(): string{
    return $this->name;
  }
  
  public function value(string $value): self{
    $this->value = $value;
    return $this;
  }
  
  public function disable(): self{
    $this->disabled = true;
    return $this;
  }
  
  public function isDisabled(): bool{
    return $this->disabled;
  }
  
  public function acceptsMissingField(): bool{
    return false;
  }
  
  public function addError(string $message): void{
    $this->errors[] = $message;
  }
  
  protected final function echoErrors(): void{
    foreach($this->errors as $error){
      echo '<p class="message error">'.$error.'</p>';
    }
  }
  
  protected final function echoLabel(string $label): void{
    echo '<label for="', $this->id, '"', ($this->disabled ? ' class="disabled"' : ''), '>', $label, '</label>';
  }
}

?>
