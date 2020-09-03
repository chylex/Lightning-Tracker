<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Data\UserId;
use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\LoadStringId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireProjectPermission;
use Pages\IAction;
use Pages\Models\Project\MemberRemoveModel;
use Pages\Views\Project\MemberRemovePage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\message;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class MemberRemoveController extends AbstractProjectController{
  private ?string $member_id;
  
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::LIST_MEMBERS);
    yield new RequireProjectPermission($project, ProjectPermissions::MANAGE_MEMBERS);
    yield new LoadStringId($this->member_id, 'member', $project);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $action = $req->getAction();
    $model = new MemberRemoveModel($req, $project, UserId::fromFormatted($this->member_id), $sess->getLogonUserIdOrThrow());
    
    if ($model->hasMember()){
      if (!$model->canRemove()){
        return message($req, 'Permission Error', 'You are not allowed to edit this member.', $project);
      }
      
      if (($action === null && $model->removeMemberSafely()) ||
          ($action === $model::ACTION_CONFIRM && $model->removeMember($req->getData()))
      ){
        return redirect(Link::fromBase($req, 'members'));
      }
    }
    
    return view(new MemberRemovePage($model->load()));
  }
}

?>
