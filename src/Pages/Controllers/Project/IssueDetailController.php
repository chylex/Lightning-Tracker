<?php
declare(strict_types = 1);

namespace Pages\Controllers\Project;

use Database\Objects\ProjectInfo;
use Generator;
use Pages\Controllers\AbstractProjectController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\IAction;
use Pages\Models\Project\IssueDetailModel;
use Pages\Views\Project\IssueDetailPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\json;
use function Pages\Actions\message;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class IssueDetailController extends AbstractProjectController{
  private ?int $issue_id;
  
  protected function projectPrerequisites(ProjectInfo $project): Generator{
    yield new LoadNumericId($this->issue_id, 'issue', $project);
  }
  
  protected function projectFinally(Request $req, Session $sess, ProjectInfo $project): IAction{
    $action = $req->getAction();
    $perms = $sess->getPermissions()->project($project);
    $model = new IssueDetailModel($req, $project, $perms, $sess->getLogonUserId(), $this->issue_id);
    
    if ($action !== null){
      if (!$model->canEditStatus()){
        return message($req, 'Permission Error', 'You do not have permission to change the status of this issue.', $project);
      }
      
      if ($action === $model::ACTION_UPDATE_TASKS){
        $model->updateCheckboxes($req->getData());
        
        if ($req->isAjax()){
          return json($model->getProgressUpdate());
        }
        else{
          return reload();
        }
      }
      elseif ($model->tryUseShortcut($action)){
        return reload();
      }
    }
    
    return view(new IssueDetailPage($model->load()));
  }
}

?>
