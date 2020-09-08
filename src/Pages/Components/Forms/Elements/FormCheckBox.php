<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

class FormCheckBox extends AbstractFormField{
  protected const CHECKED_VALUE = 'on';
  
  private ?string $label;
  
  public final function label(string $label): self{
    $this->label = $label;
    return $this;
  }
  
  /**
   * @param ?string|bool $value
   * @return $this
   */
  public final function value($value): self{
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
  
  protected final function getLabel(): ?string{
    return $this->label;
  }
  
  public final function acceptsMissingField(): bool{
    return true;
  }
  
  public function echoBody(): void{
    $checked_value = self::CHECKED_VALUE;
    
    $id = $this->getId();
    $name = $this->getName();
    $label = $this->label ?? $name;
    
    $checked_attr = $this->value !== $checked_value ? '' : ' checked';
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    
    echo <<<HTML
<div class="field-group flex">
  <input id="$id" name="$name" type="checkbox" value="$checked_value" $checked_attr$disabled_attr>
HTML;
    
    $this->echoLabel($label);
    $this->echoErrors();
    
    echo <<<HTML
</div>
HTML;
  }
}

?>
