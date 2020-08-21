<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\IViewable;

final class FormSelectOption implements IViewable{
  private string $value;
  private string $text;
  private ?string $class;
  private bool $selected = false;
  
  public function __construct(string $value, string $text, ?string $class){
    $this->value = $value;
    $this->text = $text;
    $this->class = $class;
  }
  
  public function selectIfValue(string $value): void{
    $this->selected = $value === $this->value;
  }
  
  public function echoBody(): void{
    $value = protect($this->value);
    $text = protect($this->text);
    
    $class_attr = $this->class !== null ? ' class="'.$this->class.'"' : '';
    $selected_attr = $this->selected ? ' selected' : '';
    
    echo '<option value="'.$value.'"'.$class_attr.$selected_attr.'>'.$text.'</option>';
  }
}

?>
