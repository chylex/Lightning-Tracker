<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Objects\TrackerInfo;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\error;

class LoadNumericId implements IControlHandler{
  private ?int $id_ref;
  private string $title;
  
  private ?TrackerInfo $tracker;
  private bool $optional = false;
  
  public function __construct(?int &$id_ref, string $title, ?TrackerInfo $tracker = null){
    $this->id_ref = &$id_ref;
    $this->title = $title;
    $this->tracker = $tracker;
  }
  
  public function allowMissing(): self{
    $this->optional = true;
    return $this;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    $issue_id = $req->getParam('id');
    
    if ($issue_id === null && $this->optional){
      $this->id_ref = null;
      return null;
    }
    
    if ($issue_id === null || !is_numeric($issue_id)){
      return error($req, 'Load Error', 'Invalid '.$this->title.' ID.', $this->tracker);
    }
    
    $this->id_ref = (int)$issue_id;
    return null;
  }
}

?>
