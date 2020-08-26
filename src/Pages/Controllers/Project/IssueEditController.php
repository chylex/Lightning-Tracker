<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\IssueDetail;
use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\IAction;
use Pages\Models\Project\IssueEditModel;
use Pages\Views\Project\IssueEditPage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\ProjectPermissions;
use Session\Session;
use function Pages\Actions\error;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class IssueEditController extends AbstractProjectController{
  private ?int $issue_id;
  
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new RequireLoginState(true);
    yield (new LoadNumericId($this->issue_id, 'issue', $project))->allowMissing();
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $perms = $sess->getPermissions()->project($project);
    $logon_user = $sess->getLogonUser();
    $model = new IssueEditModel($req, $project, $perms, $logon_user, $this->issue_id);
    
    if ($model->isNewIssue()){
      $perms->require(ProjectPermissions::CREATE_ISSUE);
    }
    else{
      $issue = $model->getIssue();
      
      if ($issue === null || $issue->getEditLevel($logon_user, $perms) === IssueDetail::EDIT_FORBIDDEN){
        return error($req, 'Permission Error', 'You do not have permission to edit this issue.', $project);
      }
    }
    
    if ($req->getAction() === $model::ACTION_CONFIRM){
      $redirect_issue_id = $model->createOrEditIssue($req->getData());
      
      if ($redirect_issue_id !== null){
        return redirect(Link::fromBase($req, 'issues', $redirect_issue_id));
      }
    }
    
    return view(new IssueEditPage($model->load()));
  }
}

?>
