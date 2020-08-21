<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireTrackerPermission;
use Pages\IAction;
use Pages\Models\Tracker\AbstractSettingsModel;
use Pages\Models\Tracker\SettingsGeneralModel;
use Pages\Views\Tracker\SettingsGeneralPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class SettingsGeneralController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireTrackerPermission($tracker, AbstractSettingsModel::PERM);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new SettingsGeneralModel($req, $tracker);
    
    if ($req->getAction() === $model::ACTION_UPDATE && $model->updateSettings($req->getData())){
      return $model->getForm()->reload($req);
    }
    
    return view(new SettingsGeneralPage($model->load()));
  }
}

?>
