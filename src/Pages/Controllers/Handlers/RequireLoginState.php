<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Pages\Controllers\IControlHandler;
use Pages\Controllers\Mixed\LoginController;
use Pages\IAction;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;

class RequireLoginState implements IControlHandler{
  private bool $should_be_logged_in;
  
  public function __construct(bool $should_be_logged_in){
    $this->should_be_logged_in = $should_be_logged_in;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    $is_logged_on = $sess->getLogonUser() !== null;
    
    if ($this->should_be_logged_in !== $is_logged_on){
      if ($this->should_be_logged_in){
        return redirect(Link::fromBase($req, 'login'.LoginController::generateReturnQuery($req)));
      }
      else{
        return redirect(Link::fromBase($req, LoginController::readReturnQuery()));
      }
    }
    
    return null;
  }
}

?>
