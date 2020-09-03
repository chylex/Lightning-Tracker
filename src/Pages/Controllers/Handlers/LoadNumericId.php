<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Objects\ProjectInfo;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\message;

class LoadNumericId implements IControlHandler{
  private ?int $id_ref;
  private string $title;
  
  private ?ProjectInfo $project;
  private bool $optional = false;
  
  public function __construct(?int &$id_ref, string $title, ?ProjectInfo $project = null){
    $this->id_ref = &$id_ref;
    $this->title = $title;
    $this->project = $project;
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
      return message($req, 'Load Error', 'Invalid '.$this->title.' ID.', $this->project);
    }
    
    $this->id_ref = (int)$issue_id;
    return null;
  }
}

?>
