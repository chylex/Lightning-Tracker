<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormTextField extends AbstractFormField{
  private ?string $label;
  private string $type = 'text';
  private ?string $placeholder = null;
  private ?string $autocomplete = null;
  
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
    $id = $this->getId();
    $name = $this->getName();
    $label = $this->label ?? $name;
    $value = protect($this->value);
    
    $placeholder_attr = $this->placeholder === null ? '' : ' placeholder="'.$this->placeholder.'"';
    $autocomplete_attr = $this->autocomplete === null ? '' : ' autocomplete="'.$this->autocomplete.'"';
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    
    echo '<div class="field-group">';
    $this->echoLabel($label);
    
    echo <<<HTML
  <input id="$id" name="$name" type="$this->type" value="$value" $placeholder_attr$autocomplete_attr$disabled_attr>
HTML;
    
    $this->echoErrors();
    echo '</div>';
  }
}

?>
