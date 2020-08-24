<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormTextArea extends AbstractFormField{
  private string $id;
  private ?string $label;
  private bool $markdown_editor = false;
  
  public function __construct(string $id, string $name){
    parent::__construct($name);
    $this->id = $id;
  }
  
  public function label(string $label): self{
    $this->label = $label;
    return $this;
  }
  
  public function markdownEditor(): self{
    $this->markdown_editor = true;
    return $this;
  }
  
  public function echoBody(): void{
    $name = $this->getName();
    $label = $this->label ?? $name;
    $value = protect($this->value);
    
    $markdown_editor_attr = $this->markdown_editor ? ' data-markdown-editor' : '';
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    $disabled_class = $this->disabled === false ? '' : ' class="disabled"';
    
    echo <<<HTML
<div class="field-group">
  <label for="$this->id"$disabled_class>$label</label>
  <textarea id="$this->id" name="$name"$markdown_editor_attr$disabled_attr>$value</textarea>
HTML;
    
    $this->echoErrors();
    
    echo <<<HTML
</div>
HTML;
  }
}

?>
