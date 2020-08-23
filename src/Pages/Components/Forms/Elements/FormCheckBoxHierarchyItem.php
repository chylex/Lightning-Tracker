<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

final class FormCheckBoxHierarchyItem extends FormCheckBox{
  private ?string $description = null;
  private string $layout_class;
  
  public function __construct(string $id, string $name){
    parent::__construct($id, $name);
  }
  
  public function description(string $description): FormCheckBoxHierarchyItem{
    $this->description = $description;
    return $this;
  }
  
  public function parent(): FormCheckBoxHierarchyItem{
    $this->layout_class = 'parent';
    return $this;
  }
  
  public function nonLastChild(): FormCheckBoxHierarchyItem{
    $this->layout_class = 'indented sibling';
    return $this;
  }
  
  public function lastChild(): FormCheckBoxHierarchyItem{
    $this->layout_class = 'indented';
    return $this;
  }
  
  public function echoBody(): void{
    $checked_value = FormCheckBox::CHECKED_VALUE;
    
    $name = $this->getName();
    $label = $this->getLabel() ?? $name;
    $description = $this->description === null ? '' : '<p>'.$this->description.'</p>';
    
    $layout_class = empty($this->layout_class) ? '' : ' '.$this->layout_class;
    $checked_attr = $this->value !== $checked_value ? '' : ' checked';
    $disabled_attr = $this->disabled === false ? '' : ' disabled';
    $disabled_class = $this->disabled === false ? '' : ' class="disabled"';
    
    echo <<<HTML
<div class="checkbox-multiline$layout_class">
  <input id="$name" name="$name" type="checkbox" value="$checked_value"$checked_attr$disabled_attr>
  <div class="checkbox-multiline-label">
    <label for="$name"$disabled_class>$label</label><div>$description
  </div>
HTML;
    
    $this->echoErrors();
    
    echo <<<HTML
  </div>
</div>
HTML;
  }
}

?>
