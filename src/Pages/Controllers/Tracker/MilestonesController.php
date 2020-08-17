<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Pages\Controllers\AbstractTrackerController;
use Pages\IAction;
use Pages\Models\Tracker\MilestonesModel;
use Pages\Views\Tracker\MilestonesPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class MilestonesController extends AbstractTrackerController{
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new MilestonesModel($req, $tracker, $sess->getPermissions());
    $action = $req->getAction();
    
    if ($action !== null){
      $data = $req->getData();
      
      if (($action === $model::ACTION_CREATE && $model->createMilestone($data)) ||
          ($action === $model::ACTION_MOVE && $model->moveMilestone($data)) ||
          ($action === $model::ACTION_TOGGLE_ACTIVE && $model->toggleActiveMilestone($data))
      ){
        return reload();
      }
    }
    
    return view(new MilestonesPage($model->load()));
  }
}

?>
