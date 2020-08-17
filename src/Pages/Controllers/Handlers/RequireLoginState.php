<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Pages\Controllers\IControlHandler;
use Pages\Controllers\Mixed\LoginController;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;

class RequireLoginState implements IControlHandler{
  private bool $should_be_logged_in;
  
  public function __construct(bool $should_be_logged_in){
    $this->should_be_logged_in = $should_be_logged_in;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    if ($this->should_be_logged_in !== $sess->isLoggedOn()){
      if ($this->should_be_logged_in){
        return redirect([BASE_URL_ENC, $req->getBasePath()->encoded(), 'login'.LoginController::getReturnQuery($req)]);
      }
      else{
        return redirect([BASE_URL_ENC, $req->getBasePath()->encoded()]);
      }
    }
    
    return null;
  }
}

?>
