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
use Pages\Models\Project\IssueDeleteModel;
use Pages\Views\Project\IssueDeletePage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class IssueDeleteController extends AbstractProjectController{
  private ?int $issue_id;
  
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield new RequireProjectPermission($project, ProjectPermissions::DELETE_ALL_ISSUES);
    yield new LoadNumericId($this->issue_id, 'issue', $project);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $model = new IssueDeleteModel($req, $project, $this->issue_id);
    
    if ($req->getAction() === $model::ACTION_CONFIRM && $model->deleteIssue($req->getData())){
      return redirect(Link::fromBase($req, 'issues'));
    }
    
    return view(new IssueDeletePage($model->load()));
  }
}

?>
