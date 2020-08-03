<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Pages\Controllers\IControlHandler;
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
      return redirect([BASE_URL_ENC,
                       $req->getBasePath()->encoded(),
                       $this->should_be_logged_in ? 'login?return='.rawurlencode(ltrim($_SERVER['REQUEST_URI'], '/')) : '']);
    }
    
    return null;
  }
}

?>
