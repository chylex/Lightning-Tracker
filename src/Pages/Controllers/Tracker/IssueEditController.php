<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\IssueDetail;
use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\IAction;
use Pages\Models\Tracker\IssueEditModel;
use Pages\Views\Tracker\IssueEditPage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\TrackerPermissions;
use Session\Session;
use function Pages\Actions\error;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class IssueEditController extends AbstractTrackerController{
  private ?int $issue_id;
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield (new LoadNumericId($this->issue_id, 'issue', $tracker))->allowMissing();
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $perms = $sess->getPermissions()->tracker($tracker);
    $logon_user = $sess->getLogonUser();
    $model = new IssueEditModel($req, $tracker, $perms, $logon_user, $this->issue_id);
    
    if ($model->isNewIssue()){
      $perms->require(TrackerPermissions::CREATE_ISSUE);
    }
    else{
      $issue = $model->getIssue();
      
      if ($issue === null || $issue->getEditLevel($logon_user, $perms) === IssueDetail::EDIT_FORBIDDEN){
        return error($req, 'Permission Error', 'You do not have permission to edit this issue.', $tracker);
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
