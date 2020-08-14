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
      if ($this->should_be_logged_in){
        $base_path = ltrim(BASE_PATH, '/');
        $base_path_len = strlen($base_path);
        $request_path = ltrim($_SERVER['REQUEST_URI'], '/');
        
        $return_path = substr($request_path, 0, $base_path_len) === $base_path ? rawurlencode(ltrim(substr($request_path, $base_path_len), '/')) : '';
        return redirect([BASE_URL_ENC, $req->getBasePath()->encoded(), 'login?return='.$return_path]);
      }
      else{
        return redirect([BASE_URL_ENC, $req->getBasePath()->encoded()]);
      }
    }
    
    return null;
  }
}

?>
