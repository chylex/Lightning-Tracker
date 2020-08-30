<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

final class FormCheckBoxHierarchyItem extends FormCheckBox{
  private ?string $description = null;
  private string $layout_class;
  
  public function description(string $description): self{
    $this->description = $description;
    return $this;
  }
  
  public function parent(): self{
    $this->layout_class = 'parent';
    return $this;
  }
  
  public function nonLastChild(): self{
    $this->layout_class = 'indented sibling';
    return $this;
  }
  
  public function lastChild(): self{
    $this->layout_class = 'indented';
    return $this;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $checked_value = FormCheckBox::CHECKED_VALUE;
    
    $id = $this->getId();
    $name = $this->getName();
    $label = $this->getLabel() ?? $name;
    $description = $this->description === null ? '' : '<p>'.$this->description.'</p>';
    
    $layout_class = empty($this->layout_class) ? '' : ' '.$this->layout_class;
    $checked_attr = $this->value !== $checked_value ? '' : ' checked';
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    
    echo <<<HTML
<div class="checkbox-multiline$layout_class">
  <input id="$id" name="$name" type="checkbox" value="$checked_value" $checked_attr$disabled_attr>
  <div class="checkbox-multiline-label">
HTML;
    
    $this->echoLabel($label);
    echo '<div>'.$description.'</div>';
    $this->echoErrors();
    
    echo <<<HTML
  </div>
</div>
HTML;
  }
}

?>
