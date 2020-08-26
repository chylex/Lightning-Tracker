<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Objects\ProjectInfo;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\error;

class RequireProjectPermission implements IControlHandler{
  private ProjectInfo $project;
  private string $permission;
  private string $message;
  
  public function __construct(ProjectInfo $project, string $permission, string $message = 'You do not have permission to view this page.'){
    $this->project = $project;
    $this->permission = $permission;
    $this->message = $message;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    if (!$sess->getPermissions()->project($this->project)->check($this->permission)){
      return error($req, 'Permission Error', $this->message, $this->project);
    }
    
    return null;
  }
}

?>
