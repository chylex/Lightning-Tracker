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
use Pages\Models\Project\MemberEditModel;
use Pages\Views\Project\MemberEditPage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\message;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class MemberEditController extends AbstractProjectController{
  private ?string $member_id;
  
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::LIST_MEMBERS);
    yield new RequireProjectPermission($project, ProjectPermissions::MANAGE_MEMBERS);
    yield new LoadStringId($this->member_id, 'member', $project);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $model = new MemberEditModel($req, $project, UserId::fromFormatted($this->member_id), $sess->getLogonUserIdOrThrow());
    
    if ($model->hasMember()){
      if (!$model->canEdit()){
        return message($req, 'Permission Error', 'You are not allowed to edit this member.', $project);
      }
      
      if ($req->getAction() === $model::ACTION_CONFIRM && $model->editMember($req->getData())){
        return redirect(Link::fromBase($req, 'members'));
      }
    }
    
    return view(new MemberEditPage($model->load()));
  }
}

?>
