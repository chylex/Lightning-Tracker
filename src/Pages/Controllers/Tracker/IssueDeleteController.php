<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Components\Forms\FormComponent;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\LoadIssueId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireTrackerPermission;
use Pages\IAction;
use Pages\Models\Tracker\IssueDeleteModel;
use Pages\Models\Tracker\IssuesModel;
use Pages\Views\Tracker\IssueDeletePage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class IssueDeleteController extends AbstractTrackerController{
  private ?int $issue_id;
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireTrackerPermission($tracker, IssuesModel::PERM_DELETE_ALL);
    yield new LoadIssueId($tracker, $this->issue_id);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new IssueDeleteModel($req, $tracker, $this->issue_id);
    $data = $req->getData();
    
    if (!empty($data)){
      $action = $data[FormComponent::ACTION_KEY] ?? '';
      
      if ($action === $model::ACTION_CONFIRM && $model->deleteIssue($data)){
        return redirect([BASE_URL_ENC, $req->getBasePath()->encoded(), 'issues']);
      }
    }
    
    return view(new IssueDeletePage($model->load()));
  }
}

?>
