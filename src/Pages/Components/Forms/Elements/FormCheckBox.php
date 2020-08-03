<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormCheckBox extends AbstractFormField{
  private const CHECKED_VALUE = 'on';
  
  private string $id;
  private ?string $label;
  
  public function __construct(string $id, string $name){
    parent::__construct($name);
    $this->id = $id;
  }
  
  public function label(string $label): self{
    $this->label = $label;
    return $this;
  }
  
  /**
   * @param ?string|bool $value
   * @return $this
   */
  public function value($value): self{
    if ($value === null){
      $this->value = '';
    }
    elseif (is_bool($value)){
      $this->value = $value ? self::CHECKED_VALUE : '';
    }
    else{
      $this->value = $value;
    }
    
    return $this;
  }
  
  public function acceptsMissingField(): bool{
    return true;
  }
  
  public function echoBody(): void{
    $checked_value = self::CHECKED_VALUE;
    
    $name = $this->getName();
    $label = $this->label ?? $name;
    
    $checked_attr = $this->value !== $checked_value ? '' : ' checked';
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    $disabled_class = $this->disabled === false ? '' : ' class="disabled"';
    
    echo <<<HTML
<div class="field-group">
  <input id="$this->id" name="$name" type="checkbox" value="$checked_value"$checked_attr$disabled_attr>
  <label for="$this->id"$disabled_class>$label</label>
HTML;
    
    $this->echoErrors();
    
    echo <<<HTML
</div>
HTML;
  }
}

?>
