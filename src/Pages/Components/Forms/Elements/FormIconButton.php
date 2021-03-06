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
  private bool $disabled = false;
  
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
  
  public function disabled(): self{
    $this->disabled = true;
    return $this;
  }
  
  public function echoBody(): void{
    $value = $this->value === null ? '' : ' name="'.FormComponent::BUTTON_KEY.'" value="'.$this->value.'"';
    $color_class = $this->color === null ? '' : ' icon-color-'.$this->color;
    $disabled_attr = $this->disabled ? ' disabled' : '';
    
    echo <<<HTML
<button type="$this->type" class="icon" $value$disabled_attr>
  <span class="icon icon-$this->icon$color_class"></span>
</button>
HTML;
  }
}

?>
