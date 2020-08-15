<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\TrackerDeleteModel;
use Pages\Models\Root\TrackersModel;
use Pages\Views\Root\TrackerDeletePage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class TrackerDeleteController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(TrackersModel::PERM_EDIT);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new TrackerDeleteModel($req, $tracker);
    
    if ($req->getAction() === $model::ACTION_CONFIRM && $model->deleteTracker($req->getData())){
      return redirect([BASE_URL_ENC]);
    }
    
    return view(new TrackerDeletePage($model->load()));
  }
}

?>
