<?php
declare(strict_types = 1);

namespace Database\Objects;

use Data\UserId;

final class UserProfile{
  private UserId $id;
  private string $name;
  private string $email;
  private ?int $sys_role_id;
  private bool $admin;
  
  public function __construct(UserId $id, string $name, string $email, ?int $sys_role_id, bool $admin){
    $this->id = $id;
    $this->name = $name;
    $this->email = $email;
    $this->sys_role_id = $sys_role_id;
    $this->admin = $admin;
  }
  
  public function getId(): UserId{
    return $this->id;
  }
  
  public function getName(): string{
    return $this->name;
  }
  
  public function getEmail(): string{
    return $this->email;
  }
  
  public function getSystemRoleId(): ?int{
    return $this->sys_role_id;
  }
  
  public function isAdmin(): bool{
    return $this->admin;
  }
}

?>
