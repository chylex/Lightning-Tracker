<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\IssueDetail;
use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\IAction;
use Pages\Models\Tracker\IssueDetailModel;
use Pages\Views\Tracker\IssueDetailPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\error;
use function Pages\Actions\json;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class IssueDetailController extends AbstractTrackerController{
  private ?int $issue_id;
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new LoadNumericId($this->issue_id, 'issue', $tracker);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $action = $req->getAction();
    $perms = $sess->getPermissions()->tracker($tracker);
    $model = new IssueDetailModel($req, $tracker, $perms, $this->issue_id);
    
    if ($action !== null){
      $issue = $model->getIssue();
      
      if ($issue !== null){
        if ($issue->getEditLevel($sess->getLogonUser(), $perms) < IssueDetail::EDIT_ALL_FIELDS){
          return error($req, 'Permission Error', 'You do not have permission to change the status of this issue.', $tracker);
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
    }
    
    return view(new IssueDetailPage($model->load()));
  }
}

?>
