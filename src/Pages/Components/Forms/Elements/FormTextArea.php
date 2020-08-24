<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormTextArea extends AbstractFormField{
  private const CONTROL_WHOLELINE_TOGGLE = 'wholeline-toggle';
  
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
HTML;
    
    if ($this->markdown_editor){
      echo '<div data-markdown-editor-controls>';
      
      self::editorButtonHtml(self::CONTROL_WHOLELINE_TOGGLE, 'heading-1', '<span class="text-heading">H</span><sub>1</sub>');
      self::editorButtonHtml(self::CONTROL_WHOLELINE_TOGGLE, 'heading-2', '<span class="text-heading">H</span><sub>2</sub>');
      self::editorButtonHtml(self::CONTROL_WHOLELINE_TOGGLE, 'heading-3', '<span class="text-heading">H</span><sub>3</sub>');
      
      echo '<div class="separator"></div>';
      
      self::editorButtonIcon(self::CONTROL_WHOLELINE_TOGGLE, 'task-unchecked', 'checkbox-unchecked');
      self::editorButtonIcon(self::CONTROL_WHOLELINE_TOGGLE, 'task-checked', 'checkbox-checked');
      
      echo '</div>';
    }
    
    echo <<<HTML
  <textarea id="$this->id" name="$name"$markdown_editor_attr$disabled_attr>$value</textarea>
HTML;
    
    $this->echoErrors();
    
    echo <<<HTML
</div>
HTML;
  }
  
  private static function editorButtonIcon(string $attr_name, string $attr_value, string $icon = null): void{
    $icon ??= $attr_value;
    
    echo <<<HTML
<button type="button" data-editor-action-type="$attr_name" data-editor-action-value="$attr_value">
  <span class="icon icon-$icon"></span>
</button>
HTML;
  }
  
  private static function editorButtonHtml(string $attr_name, string $attr_value, string $html): void{
    echo <<<HTML
<button type="button" data-editor-action-type="$attr_name" data-editor-action-value="$attr_value">
  $html
</button>
HTML;
  }
}

?>
