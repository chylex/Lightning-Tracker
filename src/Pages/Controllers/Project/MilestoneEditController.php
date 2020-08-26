<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireProjectPermission;
use Pages\IAction;
use Pages\Models\Project\MilestoneEditModel;
use Pages\Views\Project\MilestoneEditPage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class MilestoneEditController extends AbstractProjectController{
  private ?int $milestone_id;
  
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::MANAGE_MILESTONES);
    yield new LoadNumericId($this->milestone_id, 'milestone', $project);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $model = new MilestoneEditModel($req, $project, $this->milestone_id);
    
    if ($req->getAction() === $model::ACTION_EDIT && $model->editMilestone($req->getData())){
      return redirect(Link::fromBase($req, 'milestones'));
    }
    
    return view(new MilestoneEditPage($model->load()));
  }
}

?>
