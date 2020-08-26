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
use Session\Permissions\TrackerPermissions;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class MembersController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireTrackerPermission($tracker, TrackerPermissions::LIST_MEMBERS);
    yield new HandleFilteringRequest();
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $action = $req->getAction();
    $perms = $sess->getPermissions()->tracker($tracker);
    $model = new MembersModel($req, $tracker, $perms);
    
    if (($action === $model::ACTION_INVITE && $perms->require(TrackerPermissions::MANAGE_MEMBERS) && $model->inviteUser($req->getData())) ||
        ($action === $model::ACTION_REMOVE && $perms->require(TrackerPermissions::MANAGE_MEMBERS) && $model->removeMember($req->getData()))
    ){
      return reload();
    }
    
    return view(new MembersPage($model->load()));
  }
}

?>
