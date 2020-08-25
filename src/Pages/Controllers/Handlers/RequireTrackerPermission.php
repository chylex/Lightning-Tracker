<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Objects\TrackerInfo;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\error;

class RequireTrackerPermission implements IControlHandler{
  private TrackerInfo $tracker;
  private string $permission;
  private string $message;
  
  public function __construct(TrackerInfo $tracker, string $permission, string $message = 'You do not have permission to view this page.'){
    $this->tracker = $tracker;
    $this->permission = $permission;
    $this->message = $message;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    if (!$sess->getPermissions()->checkTracker($this->tracker, $this->permission)){
      return error($req, 'Permission Error', $this->message, $this->tracker);
    }
    
    return null;
  }
}

?>
