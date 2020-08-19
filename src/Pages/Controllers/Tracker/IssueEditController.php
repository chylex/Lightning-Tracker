<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\IAction;
use Pages\Models\Tracker\IssueEditModel;
use Pages\Models\Tracker\IssuesModel;
use Pages\Views\Tracker\IssueEditPage;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class IssueEditController extends AbstractTrackerController{
  private ?int $issue_id;
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield (new LoadNumericId($this->issue_id, 'issue', $tracker))->allowMissing();
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $perms = $sess->getPermissions();
    $model = new IssueEditModel($req, $tracker, $perms, $this->issue_id);
    
    $logon_user = $sess->getLogonUser();
    
    if (!$model->isNewIssue()){
      $issue = $model->getIssue();
      
      if ($issue === null || !$issue->isAuthorOrAssignee($logon_user)){
        $perms->requireTracker($tracker, IssuesModel::PERM_EDIT_ALL);
      }
    }
    
    if ($req->getAction() === $model::ACTION_CONFIRM){
      $new_issue_id = $model->createOrEditIssue($req->getData(), $logon_user);
      
      if ($new_issue_id !== null){
        return redirect(Link::fromBase($req, 'issues', $new_issue_id));
      }
    }
    
    return view(new IssueEditPage($model->load()));
  }
}

?>
