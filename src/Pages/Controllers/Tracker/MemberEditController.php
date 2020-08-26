<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\LoadStringId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireTrackerPermission;
use Pages\IAction;
use Pages\Models\Tracker\MemberEditModel;
use Pages\Views\Tracker\MemberEditPage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\TrackerPermissions;
use Session\Session;
use function Pages\Actions\error;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class MemberEditController extends AbstractTrackerController{
  private ?string $member_name;
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireTrackerPermission($tracker, TrackerPermissions::LIST_MEMBERS);
    yield new RequireTrackerPermission($tracker, TrackerPermissions::MANAGE_MEMBERS);
    yield new LoadStringId($this->member_name, 'member', $tracker);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new MemberEditModel($req, $tracker, $this->member_name, $sess->getLogonUserIdOrThrow());
    
    if (!$model->canEdit()){
      return error($req, 'Permission Error', 'You are not allowed to edit this member.', $tracker);
    }
    
    if ($req->getAction() === $model::ACTION_EDIT && $model->editMember($req->getData())){
      return redirect(Link::fromBase($req, 'members'));
    }
    
    return view(new MemberEditPage($model->load()));
  }
}

?>
