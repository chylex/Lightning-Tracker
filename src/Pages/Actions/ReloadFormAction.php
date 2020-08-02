<?php
declare(strict_types = 1);

namespace Pages\Actions;

use Pages\IAction;
use Routing\Request;

class ReloadFormAction implements IAction{
  private array $data;
  
  public function __construct(array $data){
    $this->data = $data;
  }
  
  public function execute(): void{
    Request::prepareSession($this->data);
    header('Location: '.$_SERVER['REQUEST_URI']);
  }
}

?>
