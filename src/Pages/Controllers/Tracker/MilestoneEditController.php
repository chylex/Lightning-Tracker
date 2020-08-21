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
use Pages\Models\Tracker\MilestoneEditModel;
use Pages\Models\Tracker\MilestonesModel;
use Pages\Views\Tracker\MilestoneEditPage;
use Routing\Link;
use Routing\Request;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class MilestoneEditController extends AbstractTrackerController{
  private ?int $milestone_id;
  
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireTrackerPermission($tracker, MilestonesModel::PERM_MANAGE);
    yield new LoadNumericId($this->milestone_id, 'milestone', $tracker);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new MilestoneEditModel($req, $tracker, $this->milestone_id);
    
    if ($req->getAction() === $model::ACTION_EDIT && $model->editMilestone($req->getData())){
      return redirect(Link::fromBase($req, 'milestones'));
    }
    
    return view(new MilestoneEditPage($model->load()));
  }
}

?>
