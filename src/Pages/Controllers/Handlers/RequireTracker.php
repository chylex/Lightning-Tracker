<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\TrackerTable;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Pages\Models\BasicRootPageModel;
use Pages\Models\ErrorModel;
use Pages\Views\ErrorPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class RequireTracker implements IControlHandler{
  private ?TrackerInfo $tracker_ref;
  
  public function __construct(?TrackerInfo &$tracker_ref){
    $this->tracker_ref = &$tracker_ref;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    $url = $req->getParam('tracker');
    
    if ($url === null){
      $page_model = new BasicRootPageModel($req);
      $error_model = new ErrorModel($page_model, 'Tracker Error', 'Tracker is missing in the URL.');
      
      return view(new ErrorPage($error_model->load()));
    }
    
    $trackers = new TrackerTable(DB::get());
    $info = $trackers->getInfoFromUrl($url, $sess->getLogonUser());
    
    if ($info === null || !$info->isVisible()){
      $page_model = new BasicRootPageModel($req);
      $error_model = new ErrorModel($page_model, 'Tracker Error', 'Tracker was not found.');
      
      return view(new ErrorPage($error_model->load()));
    }
    
    $this->tracker_ref = $info->getTracker();
    return null;
  }
}

?>
