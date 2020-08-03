<?php
declare(strict_types = 1);

namespace Session;

use Database\Objects\UserProfile;

final class SessionLoginInfo{
  public static function guest(): self{
    return new self(null);
  }
  
  public static function user(UserProfile $user): self{
    return new self($user);
  }
  
  private ?UserProfile $logon_user;
  private Permissions $permissions;
  
  private function __construct(?UserProfile $logon_user){
    $this->logon_user = $logon_user;
    $this->permissions = new Permissions($logon_user);
  }
  
  public function getLogonUser(): ?UserProfile{
    return $this->logon_user;
  }
  
  public function getPermissions(): Permissions{
    return $this->permissions;
  }
}

?>
