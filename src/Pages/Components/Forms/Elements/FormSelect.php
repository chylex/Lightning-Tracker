<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormSelect extends AbstractFormField{
  private ?string $label;
  private bool $dropdown = false;
  private bool $optional = false;
  
  /**
   * @var FormSelectOption[]
   */
  private array $options = [];
  
  public function label(string $label): self{
    $this->label = $label;
    return $this;
  }
  
  public function dropdown(): self{
    $this->dropdown = true;
    return $this;
  }
  
  public function optional(): self{
    $this->optional = true;
    return $this;
  }
  
  public function value(?string $value): self{
    if ($this->optional && $value === null){
      return parent::value('');
    }
    else{
      return parent::value($value);
    }
  }
  
  public function addOption(string $value, string $text, ?string $class = null): self{
    $this->options[] = new FormSelectOption($value, $text, $class);
    return $this;
  }
  
  public function acceptsMissingField(): bool{
    return $this->optional;
  }
  
  public function echoBody(): void{
    $id = $this->getId();
    $name = $this->getName();
    $label = $this->label ?? $name;
    
    $size_attr = $this->dropdown ? '' : ' size="'.count($this->options).'"';
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    
    echo '<div class="field-group">';
    $this->echoLabel($label);
    
    echo <<<HTML
  <select id="$id" name="$name" $size_attr$disabled_attr>
HTML;
    
    foreach($this->options as $option){
      $option->selectIfValue($this->value);
      $option->echoBody();
    }
    
    echo '</select>';
    
    $this->echoErrors();
    echo '</div>';
  }
}

?>
