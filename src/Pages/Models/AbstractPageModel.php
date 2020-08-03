<?php
declare(strict_types = 1);

namespace Pages\Models;

use Database\Objects\UserProfile;
use LogicException;
use Pages\Components\Navigation\NavigationComponent;
use Pages\Components\Text;
use Pages\IModel;
use Routing\Request;
use Session\Session;

abstract class AbstractPageModel implements IModel{
  private Request $req;
  private NavigationComponent $nav;
  
  private bool $is_loaded = false;
  
  public function __construct(Request $req){
    $this->req = $req;
  }
  
  protected abstract function createNavigation(): NavigationComponent;
  protected abstract function setupNavigation(NavigationComponent $nav, ?UserProfile $logon_user): void;
  
  public function load(): IModel{
    $this->is_loaded = true;
    $logon_user = Session::get()->getLogonUser();
    
    $this->nav = $this->createNavigation();
    $this->setupNavigation($this->nav, $logon_user);
    
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
