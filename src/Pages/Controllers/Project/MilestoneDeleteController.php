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
use Pages\Models\Project\MilestoneDeleteModel;
use Pages\Views\Project\MilestoneDeletePage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class MilestoneDeleteController extends AbstractProjectController{
  private ?int $issue_id;
  
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::MANAGE_MILESTONES);
    yield new LoadNumericId($this->issue_id, 'milestone', $project);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $action = $req->getAction();
    $model = new MilestoneDeleteModel($req, $project, $this->issue_id);
    
    if (($action === null && $model->deleteMilestoneSafely()) ||
        ($action === $model::ACTION_CONFIRM && $model->deleteMilestone($req->getData()))
    ){
      return redirect(Link::fromBase($req, 'milestones'));
    }
    
    return view(new MilestoneDeletePage($model->load()));
  }
}

?>
