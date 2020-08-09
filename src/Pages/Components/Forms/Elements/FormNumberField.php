<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormNumberField extends AbstractFormField{
  private string $id;
  private ?string $label;
  private int $min;
  private int $max;
  private int $step = 1;
  
  public function __construct(string $id, string $name, int $min, int $max){
    parent::__construct($name);
    $this->id = $id;
    $this->min = $min;
    $this->max = $max;
  }
  
  public function label(string $label): self{
    $this->label = $label;
    return $this;
  }
  
  public function step(int $step): self{
    $this->step = $step;
    return $this;
  }
  
  public function echoBody(): void{
    $name = $this->getName();
    $label = $this->label ?? $name;
    
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    $disabled_class = $this->disabled === false ? '' : ' class="disabled"';
    
    // TODO rewrite steps using JS to avoid form validation
    
    echo <<<HTML
<div class="field-group">
  <label for="$this->id"$disabled_class>$label</label>
  <input id="$this->id" name="$name" type="number" min="$this->min" max="$this->max" data-step="$this->step" value="$this->value"$disabled_attr>
HTML;
    
    $this->echoErrors();
    
    echo <<<HTML
</div>
HTML;
  }
}

?>
