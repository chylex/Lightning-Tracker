<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\Components\Forms\FormComponent;
use Pages\IViewable;

final class FormIconButton implements IViewable{
  private string $type;
  private string $icon;
  private ?string $color = null;
  private ?string $value = null;
  
  public function __construct(string $type, string $icon){
    $this->type = $type;
    $this->icon = $icon;
  }
  
  public function color(string $color): self{
    $this->color = $color;
    return $this;
  }
  
  public function value(string $value): self{
    $this->value = $value;
    return $this;
  }
  
  public function echoBody(): void{
    $value = $this->value === null ? '' : ' name="'.FormComponent::SUB_ACTION_KEY.'" value="'.$this->value.'"';
    $color_class = $this->color === null ? '' : ' icon-color-'.$this->color;
    
    echo <<<HTML
<button type="$this->type" class="icon"$value>
  <span class="icon icon-$this->icon$color_class"></span>
</button>
HTML;
  }
}

?>
