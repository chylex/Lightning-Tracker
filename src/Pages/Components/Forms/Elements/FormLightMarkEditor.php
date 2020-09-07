<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\AbstractFormField;

final class FormLightMarkEditor extends AbstractFormField{
  private const CONTROL_WHOLELINE_TOGGLE = 'wholeline-toggle';
  
  public static function echoHead(): void{
    if (DEBUG){
      echo '<link rel="stylesheet" type="text/css" href="~resources/css/editor.css?v='.TRACKER_RESOURCE_VERSION.'">';
    }
    
    echo '<script type="text/javascript" src="~resources/js/editor.js?v='.TRACKER_RESOURCE_VERSION.'"></script>';
  }
  
  private ?string $label;
  
  public function label(string $label): self{
    $this->label = $label;
    return $this;
  }
  
  public function echoBody(): void{
    $id = $this->getId();
    $name = $this->getName();
    $label = $this->label ?? $name;
    $value = protect($this->value);
    
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    
    echo '<div class="field-group">';
    $this->echoLabel($label);
    
    echo '<div data-markdown-editor-controls>';
    
    self::editorButtonHtml(self::CONTROL_WHOLELINE_TOGGLE, 'heading-1', '<span class="text-heading">H</span><sub>1</sub>');
    self::editorButtonHtml(self::CONTROL_WHOLELINE_TOGGLE, 'heading-2', '<span class="text-heading">H</span><sub>2</sub>');
    self::editorButtonHtml(self::CONTROL_WHOLELINE_TOGGLE, 'heading-3', '<span class="text-heading">H</span><sub>3</sub>');
    
    echo '<div class="separator"></div>';
    
    self::editorButtonIcon(self::CONTROL_WHOLELINE_TOGGLE, 'task-unchecked', 'checkbox-unchecked');
    self::editorButtonIcon(self::CONTROL_WHOLELINE_TOGGLE, 'task-checked', 'checkbox-checked');
    
    echo '</div>';
    
    echo '<textarea id="'.$id.'" name="'.$name.'" data-markdown-editor'.$disabled_attr.'>'.$value.'</textarea>';
    
    $this->echoErrors();
    echo '</div>';
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
