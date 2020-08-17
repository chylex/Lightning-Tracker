<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class Html implements IViewable{
  private string $html;
  
  public function __construct(string $html){
    $this->html = $html;
  }
  
  public function echoBody(): void{
    echo $this->html;
  }
}

?>
