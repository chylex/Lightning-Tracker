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
use Pages\Views\Tracker\IssueEditPage;
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
    $model = new IssueEditModel($req, $tracker, $sess->getPermissions(), $this->issue_id);
    $data = $req->getData();
    
    if (!empty($data)){
      $new_issue_id = $model->createOrEditIssue($data, $sess->getLogonUser());
      
      if ($new_issue_id !== null){
        return redirect([BASE_URL_ENC, $req->getBasePath()->encoded(), 'issues/'.$new_issue_id]);
      }
    }
    
    return view(new IssueEditPage($model->load()));
  }
}

?>
