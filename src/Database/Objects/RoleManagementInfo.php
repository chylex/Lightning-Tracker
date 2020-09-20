<?php
declare(strict_types = 1);

namespace Database\Objects;

final class RoleManagementInfo{
  private RoleInfo $role_info;
  private array $perms;
  private int $ordering_limit;
  
  public function __construct(RoleInfo $role_info, array $perms, int $ordering_limit){
    $this->role_info = $role_info;
    $this->perms = $perms;
    $this->ordering_limit = $ordering_limit;
  }
  
  public function getRole(): RoleInfo{
    return $this->role_info;
  }
  
  public function getPerms(): array{
    return $this->perms;
  }
  
  public function canMoveUp(): bool{
    $ordering = $this->role_info->getOrdering();
    return $ordering !== 0 && $ordering !== 1;
  }
  
  public function canMoveDown(): bool{
    $ordering = $this->role_info->getOrdering();
    return $ordering !== 0 && $ordering !== $this->ordering_limit;
  }
}

?>
