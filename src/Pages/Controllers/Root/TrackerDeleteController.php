<?php
declare(strict_types = 1);

namespace Pages\Controllers\Root;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\RequireLoginState;
use Pages\Controllers\Handlers\RequireSystemPermission;
use Pages\IAction;
use Pages\Models\Root\TrackerDeleteModel;
use Pages\Views\Root\TrackerDeletePage;
use Routing\Link;
use Routing\Request;
use Session\Permissions\SystemPermissions;
use Session\Session;
use function Pages\Actions\redirect;
use function Pages\Actions\view;

class TrackerDeleteController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new RequireLoginState(true);
    yield new RequireSystemPermission(SystemPermissions::LIST_PUBLIC_TRACKERS);
    yield new RequireSystemPermission(SystemPermissions::MANAGE_TRACKERS);
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new TrackerDeleteModel($req, $tracker);
    
    if ($req->getAction() === $model::ACTION_CONFIRM && $model->deleteTracker($req->getData())){
      return redirect(Link::fromRoot());
    }
    
    return view(new TrackerDeletePage($model->load()));
  }
}

?>
