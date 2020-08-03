<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Objects\TrackerInfo;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Pages\Models\BasicTrackerPageModel;
use Pages\Models\ErrorModel;
use Pages\Views\ErrorPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

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
      $page_model = new BasicTrackerPageModel($req, $this->tracker);
      $error_model = new ErrorModel($page_model, 'Permission Error', $this->message);
      
      return view(new ErrorPage($error_model->load()));
    }
    
    return null;
  }
}

?>
