<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;
use function Database\protect;

final class FormTextArea extends AbstractFormField{
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
  
  public function echoBody(): void{
    $name = $this->getName();
    $label = $this->label ?? $name;
    $value = protect($this->value);
    
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    $disabled_class = $this->disabled === false ? '' : ' class="disabled"';
    
    echo <<<HTML
<div class="field-group">
  <label for="$this->id"$disabled_class>$label</label>
  <textarea id="$this->id" name="$name" $disabled_attr>$value</textarea>
HTML;
    
    $this->echoErrors();
    
    echo <<<HTML
</div>
HTML;
  }
}

?>
