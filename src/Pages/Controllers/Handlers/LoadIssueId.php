<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Objects\TrackerInfo;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Pages\Models\BasicTrackerPageModel;
use Pages\Models\ErrorModel;
use Pages\Views\ErrorPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class LoadIssueId implements IControlHandler{
  private TrackerInfo $tracker;
  private ?int $issue_id_ref;
  private bool $optional = false;
  
  public function __construct(TrackerInfo $tracker, ?int &$issue_id_ref){
    $this->tracker = $tracker;
    $this->issue_id_ref = &$issue_id_ref;
  }
  
  public function allowMissing(): self{
    $this->optional = true;
    return $this;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    $issue_id = $req->getParam('id');
    
    if ($issue_id === null && $this->optional){
      $this->issue_id_ref = null;
      return null;
    }
    
    if ($issue_id === null || !is_numeric($issue_id)){
      $page_model = new BasicTrackerPageModel($req, $this->tracker);
      $error_model = new ErrorModel($page_model, 'Issue Error', 'Invalid issue ID.');
      
      return view(new ErrorPage($error_model->load()));
    }
    
    $this->issue_id_ref = (int)$issue_id;
    return null;
  }
}

?>
