<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Pages\IAction;
use Pages\IController;
use Routing\Request;
use Routing\RouterException;
use Session\Session;

class FaviconController implements IController{
  public function run(Request $req, Session $sess): IAction{
    return new class implements IAction{
      public function execute(): void{
        http_response_code(RouterException::STATUS_NOT_FOUND);
      }
    };
  }
}

?>
