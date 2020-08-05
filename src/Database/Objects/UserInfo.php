<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class UserInfo{
  private int $id;
  private string $name;
  private string $email;
  private ?string $role_title;
  private string $registration_date;
  
  public function __construct(int $id, string $name, string $email, ?string $role_title, string $registration_date){
    $this->id = $id;
    $this->name = $name;
    $this->email = $email;
    $this->role_title = $role_title;
    $this->registration_date = $registration_date;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getNameSafe(): string{
    return protect($this->name);
  }
  
  public function getEmailSafe(): string{
    return protect($this->email);
  }
  
  public function getRoleTitleSafe(): ?string{
    return $this->role_title === null ? null : protect($this->role_title);
  }
  
  public function getRegistrationDate(): string{
    return $this->registration_date;
  }
}

?>