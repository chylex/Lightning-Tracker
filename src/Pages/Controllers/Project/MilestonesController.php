<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Pages\Controllers\AbstractProjectController;
use Pages\IAction;
use Pages\Models\Project\MilestonesModel;
use Pages\Views\Project\MilestonesPage;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class MilestonesController extends AbstractProjectController{
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $action = $req->getAction();
    $perms = $sess->getPermissions()->project($project);
    $model = new MilestonesModel($req, $project, $perms, $sess->getLogonUserId());
    
    if ($action !== null){
      $data = $req->getData();
      
      if (($action === $model::ACTION_CREATE && $perms->require(ProjectPermissions::MANAGE_MILESTONES) && $model->createMilestone($data)) ||
          ($action === $model::ACTION_MOVE && $perms->require(ProjectPermissions::MANAGE_MILESTONES) && $model->moveMilestone($data)) ||
          ($action === $model::ACTION_TOGGLE_ACTIVE && $model->toggleActiveMilestone($data))
      ){
        return reload();
      }
    }
    
    return view(new MilestonesPage($model->load()));
  }
}

?>
