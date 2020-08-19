<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\LoadNumericId;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireTrackerPermission;
use Pages\IAction;
use Pages\Models\Tracker\MilestoneDeleteModel;
use Pages\Models\Tracker\MilestonesModel;
use Pages\Views\Tracker\MilestoneDeletePage;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class MilestoneDeleteController extends AbstractTrackerController{
  private ?int $issue_id;
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireTrackerPermission($tracker, MilestonesModel::PERM_EDIT);
    yield new LoadNumericId($this->issue_id, 'milestone', $tracker);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $action = $req->getAction();
    $model = new MilestoneDeleteModel($req, $tracker, $this->issue_id);
    
    if (($action === null && $model->deleteMilestoneSafely()) ||
        ($action === $model::ACTION_CONFIRM && $model->deleteMilestone($req->getData()))
    ){
      return redirect(Link::fromBase($req, 'milestones'));
    }
    
    return view(new MilestoneDeletePage($model->load()));
  }
}

?>
