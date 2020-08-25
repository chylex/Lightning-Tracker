<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Tables\TrackerTable;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\error;

class LoadTracker implements IControlHandler{
  private ?TrackerInfo $tracker_ref;
  private bool $optional = false;
  
  public function __construct(?TrackerInfo &$tracker_ref){
    $this->tracker_ref = &$tracker_ref;
  }
  
  public function allowMissing(): self{
    $this->optional = true;
    return $this;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    $url = $req->getParam('tracker');
    
    if ($url === null && $this->optional){
      $this->tracker_ref = null;
      return null;
    }
    
    if ($url === null){
      return error($req, 'Tracker Error', 'Tracker is missing in the URL.');
    }
    
    $trackers = new TrackerTable(DB::get());
    $info = $trackers->getInfoFromUrl($url, $sess->getLogonUser());
    
    if ($info === null || !$info->isVisible()){
      return error($req, 'Tracker Error', 'Tracker was not found.');
    }
    
    $this->tracker_ref = $info->getTracker();
    return null;
  }
}

?>
