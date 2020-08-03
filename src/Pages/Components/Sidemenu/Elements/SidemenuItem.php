<?php
declare(strict_types = 1);

namespace Pages\Components\Sidemenu\Elements;

use Pages\Components\Text;

final class SidemenuItem{
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
    $class = $this->active ? ' class="active"' : '';
    
    echo '<li'.$class.'><a href="'.$this->url.'">';
    $this->title->echoBody();
    echo '</a></li>';
  }
}

?>
