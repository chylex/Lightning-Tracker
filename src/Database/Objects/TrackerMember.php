<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class TrackerMember{
  private int $user_id;
  private string $user_name;
  private ?string $role_title;
  
  public function __construct(int $user_id, string $user_name, ?string $role_title){
    $this->user_id = $user_id;
    $this->user_name = $user_name;
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
  
  public function getRoleTitleSafe(): ?string{
    return $this->role_title === null ? null : protect($this->role_title);
  }
}

?>
