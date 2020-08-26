<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Generator;
use Pages\Controllers\AbstractTrackerController;
use Pages\Controllers\Handlers\HandleFilteringRequest;
use Pages\IAction;
use Pages\Models\Tracker\IssuesModel;
use Pages\Views\Tracker\IssuesPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class IssuesController extends AbstractTrackerController{
  protected function trackerHandlers(TrackerInfo $tracker): Generator{
    yield new HandleFilteringRequest();
  }
  
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    return view(new IssuesPage((new IssuesModel($req, $tracker, $sess->getPermissions()->tracker($tracker)))->load()));
  }
}

?>
