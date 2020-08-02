<?php
declare(strict_types = 1);

namespace Pages\Controllers;

use Generator;
use Pages\IAction;
use Pages\IController;
use Routing\Request;
use Session\Session;

abstract class AbstractHandlerController implements IController{
  public final function run(Request $req, Session $sess): IAction{
    /** @var IControlHandler $handler */
    foreach($this->prerequisites() as $handler){
      $result = $handler->run($req, $sess);
      
      if ($result !== null){
        return $result;
      }
    }
    
    return $this->finally($req, $sess);
  }
  
  protected abstract function prerequisites(): Generator;
  protected abstract function finally(Request $req, Session $sess): IAction;
}

?>
