<?php
declare(strict_types = 1);

namespace Database\Objects;

final class ProjectMember{
  private int $user_id;
  private string $user_name;
  private ?int $role_id;
  private ?string $role_title;
  
  public function __construct(int $user_id, string $user_name, ?int $role_id, ?string $role_title){
    $this->user_id = $user_id;
    $this->user_name = $user_name;
    $this->role_id = $role_id;
    $this->role_title = $role_title;
  }
  
  public function getUserId(): int{
    return $this->user_id;
  }
  
  public function getUserName(): string{
    return $this->user_name;
  }
  
  public function getUserNameSafe(): string{
    return protect($this->user_name);
  }
  
  public function getRoleId(): ?int{
    return $this->role_id;
  }
  
  public function getRoleTitleSafe(): ?string{
    return $this->role_title === null ? null : protect($this->role_title);
  }
}

?>
