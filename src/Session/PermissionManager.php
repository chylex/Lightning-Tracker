<?php
declare(strict_types = 1);

namespace Session;

use Database\DB;
use Database\Objects\TrackerInfo;
use Database\Objects\UserProfile;
use Database\Tables\SystemPermTable;
use Database\Tables\TrackerPermTable;
use Exception;
use Logging\Log;
use Session\Permissions\SystemPermissions;
use Session\Permissions\TrackerPermissions;

final class PermissionManager{
  private ?UserProfile $user;
  private ?SystemPermissions $system;
  private array $tracker;
  
  public function __construct(?UserProfile $user){
    $this->user = $user;
    $this->system = null;
    $this->tracker = [];
  }
  
  public function system(): SystemPermissions{
    if ($this->system === null){
      if ($this->user !== null && $this->user->isAdmin()){
        $this->system = SystemPermissions::permitAll();
      }
      else{
        try{
          $perms = new SystemPermTable(DB::get());
          $this->system = SystemPermissions::permitList($perms->listPerms($this->user));
        }catch(Exception $e){
          Log::critical($e);
          $this->system = SystemPermissions::permitList([]);
        }
      }
    }
    
    return $this->system;
  }
  
  public function tracker(TrackerInfo $tracker): TrackerPermissions{
    $id = $tracker->getId();
    
    if (!isset($this->tracker[$id])){
      if ($this->user !== null && ($this->user->isAdmin() || $tracker->getOwnerId() === $this->user->getId())){
        $this->tracker[$id] = TrackerPermissions::permitAll();
      }
      else{
        try{
          $perms = new TrackerPermTable(DB::get(), $tracker);
          $this->tracker[$id] = TrackerPermissions::permitList($perms->listUserPerms($this->user));
        }catch(Exception $e){
          Log::critical($e);
          $this->tracker[$id] = TrackerPermissions::permitList([]);
        }
      }
    }
    
    return $this->tracker[$id];
  }
}

?>
