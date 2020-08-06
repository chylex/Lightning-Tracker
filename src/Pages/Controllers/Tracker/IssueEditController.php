<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\IAction;
use Pages\Models\BasicTrackerPageModel;
use Pages\Models\ErrorModel;
use Pages\Models\Tracker\IssueEditModel;
use Pages\Views\ErrorPage;
use Pages\Views\Tracker\IssueEditPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class IssueEditController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $issue_id = $req->getParam('id');
    
    if ($issue_id === null){
      $model = new IssueEditModel($req, $tracker, $sess->getPermissions(), null);
    }
    elseif (is_numeric($issue_id)){
      $model = new IssueEditModel($req, $tracker, $sess->getPermissions(), (int)$issue_id);
    }
    else{
      $page_model = new BasicTrackerPageModel($req, $tracker);
      $error_model = new ErrorModel($page_model, 'Issue Error', 'Invalid issue ID.');
      
      return view(new ErrorPage($error_model->load()));
    }
    
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
