<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormNumberField extends AbstractFormField{
  private ?string $label;
  private int $min;
  private int $max;
  private int $step = 1;
  
  public function __construct(string $id, string $name, int $min, int $max){
    parent::__construct($id, $name);
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
    $id = $this->getId();
    $name = $this->getName();
    $label = $this->label ?? $name;
    $value = min($this->max, max($this->min, (int)$this->value));
    
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    
    echo '<div class="field-group">';
    $this->echoLabel($label);
    
    echo <<<HTML
  <input id="$id" name="$name" type="number" min="$this->min" max="$this->max" data-step="$this->step" value="$value" $disabled_attr>
HTML;
    
    $this->echoErrors();
    echo '</div>';
  }
}

?>
