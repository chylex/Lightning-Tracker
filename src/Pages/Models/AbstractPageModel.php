<?php
declare(strict_types = 1);

namespace Pages\Models;

use LogicException;
use Pages\Components\Navigation\NavigationComponent;
use Pages\Components\Text;
use Pages\IModel;
use Routing\Request;
use Session\Permissions;
use Session\Session;

abstract class AbstractPageModel implements IModel{
  private Request $req;
  private NavigationComponent $nav;
  
  private bool $is_loaded = false;
  
  public function __construct(Request $req){
    $this->req = $req;
  }
  
  protected abstract function createNavigation(): NavigationComponent;
  protected abstract function setupNavigation(NavigationComponent $nav, Permissions $perms): void;
  
  public function load(): IModel{
    $this->is_loaded = true;
    
    $sess = Session::get();
    $logon_user = $sess->getLogonUser();
    
    $this->nav = $this->createNavigation();
    $this->setupNavigation($this->nav, $sess->getPermissions());
    
    if ($logon_user !== null){
      $this->nav->addRight(Text::withIcon($logon_user->getNameSafe(), 'user'), '/account');
    }
    else{
      $this->nav->addRight(Text::withIcon('Login', 'enter'), '/login');
      
      if (SYS_ENABLE_REGISTRATION){
        $this->nav->addRight(Text::withIcon('Register', 'user'), '/register');
      }
    }
    
    return $this;
  }
  
  public function ensureLoaded(): void{
    if (!$this->is_loaded){
      throw new LogicException('Model has not been loaded.');
    }
  }
  
  public function getReq(): Request{
    return $this->req;
  }
  
  public function getNav(): NavigationComponent{
    return $this->nav;
  }
}

?>
