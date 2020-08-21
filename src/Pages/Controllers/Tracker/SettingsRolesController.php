<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireTrackerPermission;
use Pages\IAction;
use Pages\Models\Tracker\AbstractSettingsModel;
use Pages\Models\Tracker\SettingsRolesModel;
use Pages\Views\Tracker\SettingsRolesPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class SettingsRolesController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireTrackerPermission($tracker, AbstractSettingsModel::PERM);
    yield new HandleFilteringRequest();
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $action = $req->getAction();
    $model = new SettingsRolesModel($req, $tracker);
  
    if (($action === $model::ACTION_CREATE && $model->createRole($req->getData())) ||
        ($action === $model::ACTION_MOVE && $model->moveRole($req->getData())) ||
        ($action === $model::ACTION_DELETE && $model->deleteRole($req->getData()))
    ){
      return reload();
    }
  
    return view(new SettingsRolesPage($model->load()));
  }
}

?>
