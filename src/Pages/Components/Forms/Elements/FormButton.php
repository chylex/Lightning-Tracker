<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\FormComponent;
use Pages\IViewable;

final class FormButton implements IViewable{
  private string $type;
  private string $label;
  private ?string $value = null;
  private ?string $icon = null;
  
  public function __construct(string $type, string $label){
    $this->type = $type;
    $this->label = $label;
  }
  
  public function value(string $value): self{
    $this->value = $value;
    return $this;
  }
  
  public function icon(string $icon): self{
    $this->icon = $icon;
    return $this;
  }
  
  public function echoBody(): void{
    $value = $this->value === null ? '' : ' name="'.FormComponent::SUB_ACTION_KEY.'" value="'.$this->value.'"';
    $icon = $this->icon === null ? '' : '<span class="icon icon-'.$this->icon.'"></span> ';
    
    echo <<<HTML
<button class="styled" type="$this->type"$value>$icon$this->label</button>
HTML;
  }
}

?>
