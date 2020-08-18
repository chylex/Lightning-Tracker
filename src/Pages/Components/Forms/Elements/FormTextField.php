<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;
use function Database\protect;

final class FormTextField extends AbstractFormField{
  private string $id;
  private ?string $label;
  private string $type = 'text';
  private ?string $placeholder = null;
  private ?string $autocomplete = null;
  
  public function __construct(string $id, string $name){
    parent::__construct($name);
    $this->id = $id;
  }
  
  public function label(string $label): self{
    $this->label = $label;
    return $this;
  }
  
  public function type(string $type): self{
    $this->type = $type;
    return $this;
  }
  
  public function placeholder(string $placeholder): self{
    $this->placeholder = $placeholder;
    return $this;
  }
  
  public function autocomplete(string $autocomplete): self{
    $this->autocomplete = $autocomplete;
    return $this;
  }
  
  public function echoBody(): void{
    $name = $this->getName();
    $label = $this->label ?? $name;
    $value = protect($this->value);
    
    $placeholder_attr = $this->placeholder === null ? '' : ' placeholder="'.$this->placeholder.'"';
    $autocomplete_attr = $this->autocomplete === null ? '' : ' autocomplete="'.$this->autocomplete.'"';
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    $disabled_class = $this->disabled === false ? '' : ' class="disabled"';
    
    echo <<<HTML
<div class="field-group">
  <label for="$this->id"$disabled_class>$label</label>
  <input id="$this->id" name="$name" type="$this->type" value="$value"$placeholder_attr$autocomplete_attr$disabled_attr>
HTML;
    
    $this->echoErrors();
    
    echo <<<HTML
</div>
HTML;
  }
}

?>
