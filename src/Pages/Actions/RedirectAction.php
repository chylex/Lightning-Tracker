<?php
declare(strict_types = 1);

namespace Pages\Actions;

use Pages\IAction;

class RedirectAction implements IAction{
  private string $url;
  
  public function __construct(string $url){
    $this->url = $url;
  }
  
  public function execute(): void{
    header('Location: '.$this->url);
  }
}

?>
