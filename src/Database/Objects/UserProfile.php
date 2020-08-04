<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class UserProfile{
  private int $id;
  private string $name;
  private string $email;
  private ?int $role_id;
  private bool $admin;
  
  public function __construct(int $id, string $name, string $email, ?int $role_id, bool $admin){
    $this->id = $id;
    $this->name = $name;
    $this->email = $email;
    $this->role_id = $role_id;
    $this->admin = $admin;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getName(): string{
    return $this->name;
  }
  
  public function getNameSafe(): string{
    return protect($this->name);
  }
  
  public function getEmailSafe(): string{
    return protect($this->email);
  }
  
  public function getRoleId(): ?int{
    return $this->role_id;
  }
  
  public function isAdmin(): bool{
    return $this->admin;
  }
}

?>
