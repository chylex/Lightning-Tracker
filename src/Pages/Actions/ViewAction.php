<?php
declare(strict_types = 1);

namespace Pages\Actions;

use Pages\IAction;
use Pages\IViewable;

class ViewAction implements IAction{
  private IViewable $view;
  
  public function __construct(IViewable $view){
    $this->view = $view;
  }
  
  public function execute(): void{
    $this->view->echoBody();
  }
}

?>
