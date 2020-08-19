<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireTrackerPermission;
use Pages\IAction;
use Pages\Models\Tracker\SettingsModel;
use Pages\Views\Tracker\SettingsPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class SettingsController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireTrackerPermission($tracker, SettingsModel::PERM);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new SettingsModel($req, $tracker);
    
    if ($req->getAction() === $model::ACTION_UPDATE && $model->updateSettings($req->getData())){
      return $model->getForm()->reload($req);
    }
    
    return view(new SettingsPage($model->load()));
  }
}

?>
