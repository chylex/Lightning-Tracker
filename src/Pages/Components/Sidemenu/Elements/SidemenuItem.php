<?php
declare(strict_types = 1);

namespace Pages\Components\Sidemenu\Elements;

use Pages\Components\Text;
use Pages\IViewable;

final class SidemenuItem implements IViewable{
  private Text $title;
  private string $url;
  private ?string $id;
  private bool $active = false;
  
  public function __construct(Text $title, string $url, ?string $id){
    $this->title = $title;
    $this->url = $url;
    $this->id = $id;
  }
  
  public function active(): self{
    $this->active = true;
    return $this;
  }
  
  public function echoBody(): void{
    $class_attr = $this->active ? ' class="active"' : '';
    $id_attr = $this->id !== null ? ' id="'.$this->id.'"' : '';
    
    echo '<li'.$class_attr.'><a'.$id_attr.' href="'.$this->url.'">';
    $this->title->echoBody();
    echo '</a></li>';
  }
}

?>
