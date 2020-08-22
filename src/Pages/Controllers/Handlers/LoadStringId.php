<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\Objects\TrackerInfo;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Pages\Models\BasicMixedPageModel;
use Pages\Models\ErrorModel;
use Pages\Views\ErrorPage;
use Routing\Request;
use Session\Session;
use function Pages\Actions\view;

class LoadStringId implements IControlHandler{
  private ?string $id_ref;
  private string $title;
  
  private ?TrackerInfo $tracker;
  private bool $optional = false;
  
  public function __construct(?string &$id_ref, string $title, ?TrackerInfo $tracker = null){
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
    
    if ($issue_id === null){
      $page_model = new BasicMixedPageModel($req, $this->tracker);
      $error_model = new ErrorModel($page_model, 'Load Error', 'Invalid '.$this->title.'.');
      
      return view(new ErrorPage($error_model->load()));
    }
    
    $this->id_ref = $issue_id;
    return null;
  }
}

?>