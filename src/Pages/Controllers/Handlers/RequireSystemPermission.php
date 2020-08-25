<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\error;

class RequireSystemPermission implements IControlHandler{
  private string $permission;
  private string $message;
  
  public function __construct(string $permission, string $message = 'You do not have permission to view this page.'){
    $this->permission = $permission;
    $this->message = $message;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    if (!$sess->getPermissions()->checkSystem($this->permission)){
      return error($req, 'Permission Error', $this->message);
    }
    
    return null;
  }
}

?>
