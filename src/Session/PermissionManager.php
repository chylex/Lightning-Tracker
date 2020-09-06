<?php
declare(strict_types = 1);

namespace Session;

use Database\DB;
use Database\Objects\ProjectInfo;
use Database\Objects\UserProfile;
use Database\Tables\ProjectPermTable;
use Database\Tables\SystemPermTable;
use Exception;
use Logging\Log;
use Session\Permissions\ProjectPermissions;
use Session\Permissions\SystemPermissions;

final class PermissionManager{
  private ?UserProfile $user;
  private ?SystemPermissions $system;
  private array $project;
  
  public function __construct(?UserProfile $user){
    $this->user = $user;
    $this->system = null;
    $this->project = [];
  }
  
  public function system(): SystemPermissions{
    if ($this->system === null){
      if ($this->user !== null && $this->user->isAdmin()){
        $this->system = SystemPermissions::permitAll();
      }
      else{
        try{
          $perms = new SystemPermTable(DB::get());
          $this->system = SystemPermissions::permitList($perms->listUserPerms($this->user));
        }catch(Exception $e){
          Log::critical($e);
          $this->system = SystemPermissions::permitList([]);
        }
      }
    }
    
    return $this->system;
  }
  
  public function project(ProjectInfo $project): ProjectPermissions{
    $id = $project->getId();
    
    if (!isset($this->project[$id])){
      if (($this->user !== null && $project->getOwnerId()->equals($this->user->getId())) || $this->system()->check(SystemPermissions::MANAGE_PROJECTS)){
        $this->project[$id] = ProjectPermissions::permitAll();
      }
      else{
        try{
          $perms = new ProjectPermTable(DB::get(), $project);
          $this->project[$id] = ProjectPermissions::permitList($perms->listUserPerms($this->user));
        }catch(Exception $e){
          Log::critical($e);
          $this->project[$id] = ProjectPermissions::permitList([]);
        }
      }
    }
    
    return $this->project[$id];
  }
}

?>
