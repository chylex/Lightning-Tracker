<?php
declare(strict_types = 1);

namespace Database\Objects;

use Data\UserId;

final class UserInfo{
  private UserId $id;
  private string $name;
  private string $email;
  private ?int $role_id;
  private ?string $role_title;
  private bool $admin;
  private string $date_registered;
  
  public function __construct(UserId $id, string $name, string $email, ?int $role_id, ?string $role_title, bool $admin, string $date_registered){
    $this->id = $id;
    $this->name = $name;
    $this->email = $email;
    $this->role_id = $role_id;
    $this->role_title = $role_title;
    $this->admin = $admin;
    $this->date_registered = $date_registered;
  }
  
  public function getId(): UserId{
    return $this->id;
  }
  
  public function getName(): string{
    return $this->name;
  }
  
  public function getNameSafe(): string{
    return protect($this->name);
  }
  
  public function getEmail(): string{
    return $this->email;
  }
  
  public function getEmailSafe(): string{
    return protect($this->email);
  }
  
  public function getRoleId(): ?int{
    return $this->role_id;
  }
  
  public function getRoleTitleSafe(): ?string{
    return $this->role_title === null ? null : protect($this->role_title);
  }
  
  public function isAdmin(): bool{
    return $this->admin;
  }
  
  public function getRegistrationDate(): string{
    return $this->date_registered;
  }
}

?>
