<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\message;

class RequireSystemPermission implements IControlHandler{
  private string $permission;
  private string $message;
  
  public function __construct(string $permission, string $message = 'You do not have permission to view this page.'){
    $this->permission = $permission;
    $this->message = $message;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    if (!$sess->getPermissions()->system()->check($this->permission)){
      return message($req, 'Permission Error', $this->message);
    }
    
    return null;
  }
}

?>
