<?php
declare(strict_types = 1);

namespace Pages\Components\Forms;

use Pages\IViewable;

final class IconButtonFormComponent implements IViewable{
  private string $url;
  private string $icon;
  private ?string $color = null;
  
  public function __construct(string $url, string $icon){
    $this->url = $url;
    $this->icon = $icon;
  }
  
  public function color(string $color): self{
    $this->color = $color;
    return $this;
  }
  
  public function echoBody(): void{
    $color_class = $this->color === null ? '' : ' icon-color-'.$this->color;
    
    echo <<<HTML
<form action="$this->url">
  <button type="submit" class="icon">
    <span class="icon icon-$this->icon$color_class"></span>
  </button>
</form>
HTML;
  }
}

?>
