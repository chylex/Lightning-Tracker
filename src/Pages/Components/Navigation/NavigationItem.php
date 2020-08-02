<?php
declare(strict_types = 1);

namespace Pages\Components\Navigation;

use Pages\Components\Text;
use Pages\IViewable;

final class NavigationItem implements IViewable{
  private Text $title;
  private string $url;
  private bool $active = false;
  
  public function __construct(Text $title, string $url){
    $this->title = $title;
    $this->url = $url;
  }
  
  public function active(): self{
    $this->active = true;
    return $this;
  }
  
  public function echoBody(): void{
    $class = 'item';
    
    if ($this->active){
      $class .= ' active';
    }
    
    echo '<div class="'.$class.'"><a href="'.$this->url.'">';
    $this->title->echoBody();
    echo '</a></div>';
  }
}

?>
