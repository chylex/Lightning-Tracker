<?php
declare(strict_types = 1);

namespace Database\Objects;

final class UserProfile{
  private int $id;
  private string $name;
  private string $email;
  private ?int $sys_role_id;
  private bool $admin;
  
  public function __construct(int $id, string $name, string $email, ?int $sys_role_id, bool $admin){
    $this->id = $id;
    $this->name = $name;
    $this->email = $email;
    $this->sys_role_id = $sys_role_id;
    $this->admin = $admin;
  }
  
  public function getId(): int{
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
