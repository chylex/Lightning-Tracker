<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Components\Forms\FormComponent;
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
    $model = new MembersModel($req, $tracker, $sess->getPermissions());
    $data = $req->getData();
    
    if (!empty($data)){
      $action = $data[FormComponent::ACTION_KEY] ?? '';
      
      if (($action === $model::ACTION_INVITE && $model->inviteUser($data)) ||
          ($action === $model::ACTION_REMOVE && $model->removeMember($data))
      ){
        return reload();
      }
    }
    
    return view(new MembersPage($model->load()));
  }
}

?>
