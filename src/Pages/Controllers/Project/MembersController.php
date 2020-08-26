<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\Controllers\Handlers\RequireProjectPermission;
use Pages\IAction;
use Pages\Models\Project\MembersModel;
use Pages\Views\Project\MembersPage;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class MembersController extends AbstractProjectController{
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireProjectPermission($project, ProjectPermissions::LIST_MEMBERS);
    yield new HandleFilteringRequest();
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $action = $req->getAction();
    $perms = $sess->getPermissions()->project($project);
    $model = new MembersModel($req, $project, $perms);
    
    if (($action === $model::ACTION_INVITE && $perms->require(ProjectPermissions::MANAGE_MEMBERS) && $model->inviteUser($req->getData())) ||
        ($action === $model::ACTION_REMOVE && $perms->require(ProjectPermissions::MANAGE_MEMBERS) && $model->removeMember($req->getData()))
    ){
      return reload();
    }
    
    return view(new MembersPage($model->load()));
  }
}

?>
