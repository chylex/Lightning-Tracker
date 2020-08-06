<?php
declare(strict_types = 1);

namespace Pages\Controllers\Tracker;

use Database\Objects\TrackerInfo;
use Pages\Controllers\AbstractTrackerController;
use Pages\IAction;
use Pages\Models\BasicTrackerPageModel;
use Pages\Views\Tracker\DashboardPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class DashboardController extends AbstractTrackerController{
  protected function runTracker(Request $req, Session $sess, TrackerInfo $tracker): IAction{
    $model = new BasicTrackerPageModel($req, $tracker);
    
    // TODO
    
    return view(new DashboardPage($model->load()));
  }
}

?>
