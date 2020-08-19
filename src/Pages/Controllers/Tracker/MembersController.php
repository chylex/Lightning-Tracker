<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\Controllers\Handlers\RequireTrackerPermission;
use Pages\IAction;
use Pages\Models\Tracker\MembersModel;
use Pages\Views\Tracker\MembersPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class MembersController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireTrackerPermission($tracker, MembersModel::PERM_LIST);
    yield new HandleFilteringRequest();
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $action = $req->getAction();
    $perms = $sess->getPermissions();
    $model = new MembersModel($req, $tracker, $perms);
    
    if (($action === $model::ACTION_INVITE && $perms->requireTracker($tracker, $model::PERM_MANAGE) && $model->inviteUser($req->getData())) ||
        ($action === $model::ACTION_REMOVE && $perms->requireTracker($tracker, $model::PERM_MANAGE) && $model->removeMember($req->getData()))
    ){
      return reload();
    }
    
    return view(new MembersPage($model->load()));
  }
}

?>
