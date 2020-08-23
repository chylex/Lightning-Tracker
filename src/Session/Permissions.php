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

final class Permissions{
  private ?UserProfile $user;
  private ?array $system;
  private array $tracker;
  
  public function __construct(?UserProfile $user){
    $this->user = $user;
    $this->system = null;
    $this->tracker = [];
  }
  
  public function checkSystem(string $permission): bool{
    if ($this->user !== null && $this->user->isAdmin()){
      return true;
    }
    
    if ($this->system === null){
      try{
        $perms = new SystemPermTable(DB::get());
        $this->system = $perms->listPerms($this->user);
      }catch(Exception $e){
        Log::critical($e);
        $this->system = [];
      }
    }
    
    return in_array($permission, $this->system);
  }
  
  public function checkTracker(TrackerInfo $tracker, string $permission): bool{
    if ($this->user !== null && ($this->user->isAdmin() || $tracker->getOwnerId() === $this->user->getId())){
      return true;
    }
    
    $id = $tracker->getId();
    
    if (!isset($this->tracker[$id])){
      try{
        $perms = new TrackerPermTable(DB::get(), $tracker);
        $this->tracker[$id] = $perms->listUserPerms($this->user);
      }catch(Exception $e){
        Log::critical($e);
        $this->tracker[$id] = [];
      }
    }
    
    return in_array($permission, $this->tracker[$id]);
  }
  
  public function requireSystem(string $permission): bool{
    if ($this->checkSystem($permission)){
      return true;
    }
    else{
      throw new PermissionException('system:'.$permission);
    }
  }
  
  public function requireTracker(TrackerInfo $tracker, string $permission): bool{
    if ($this->checkTracker($tracker, $permission)){
      return true;
    }
    else{
      throw new PermissionException('tracker:'.$permission);
    }
  }
}

?>
