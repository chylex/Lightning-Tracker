<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Pages\Components\Forms\FormComponent;
use Pages\Controllers\AbstractTrackerController;
use Pages\IAction;
use Pages\Models\BasicTrackerPageModel;
use Pages\Models\ErrorModel;
use Pages\Models\Tracker\IssueDetailModel;
use Pages\Views\ErrorPage;
use Pages\Views\Tracker\IssueDetailPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\reload;
use function Pages\Actions\view;

class IssueDetailController extends AbstractTrackerController{
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $issue_id = $req->getParam('id');
    
    if ($issue_id === null || !is_numeric($issue_id)){
      $page_model = new BasicTrackerPageModel($req, $tracker);
      $error_model = new ErrorModel($page_model, 'Issue Error', 'Invalid issue ID.');
      
      return view(new ErrorPage($error_model->load()));
    }
    
    $model = new IssueDetailModel($req, $tracker, $sess->getPermissions(), (int)$issue_id);
    $data = $req->getData();
    
    if (!empty($data)){
      $action = $data[FormComponent::ACTION_KEY] ?? '';
      
      if ($action === $model::ACTION_UPDATE_TASKS){
        $model->updateCheckboxes($data);
        return reload();
      }
    }
    
    return view(new IssueDetailPage($model->load()));
  }
}

?>
