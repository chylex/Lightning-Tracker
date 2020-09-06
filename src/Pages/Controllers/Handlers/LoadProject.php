<?php
declare(strict_types = 1);

namespace Pages\Controllers\Handlers;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Tables\ProjectTable;
use Pages\Controllers\IControlHandler;
use Pages\IAction;
use Routing\Request;
use Session\Session;
use function Pages\Actions\message;

class LoadProject implements IControlHandler{
  private ?ProjectInfo $project_ref;
  private bool $optional = false;
  
  public function __construct(?ProjectInfo &$project_ref){
    $this->project_ref = &$project_ref;
  }
  
  public function allowMissing(): self{
    $this->optional = true;
    return $this;
  }
  
  public function run(Request $req, Session $sess): ?IAction{
    $url = $req->getParam('project');
    
    if ($url === null && $this->optional){
      $this->project_ref = null;
      return null;
    }
    
    if ($url === null){
      return message($req, 'Project Error', 'Project is missing in the URL.');
    }
    
    $projects = new ProjectTable(DB::get());
    $info = $projects->getInfoFromUrl($url, $sess->getLogonUser(), $sess->getPermissions()->system());
    
    if ($info === null || !$info->isVisible()){
      return message($req, 'Project Error', 'Project was not found.');
    }
    
    $this->project_ref = $info->getProject();
    return null;
  }
}

?>
