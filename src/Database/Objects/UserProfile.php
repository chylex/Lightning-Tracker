<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class UserProfile{
  private int $id;
  private string $name;
  private string $email;
  
  public function __construct(int $id, string $name, string $email){
    $this->id = $id;
    $this->name = $name;
    $this->email = $email;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getName(): string{
    return $this->name;
  }
  
  public function getNameSafe(): string{
    return protect($this->getName());
  }
  
  public function getEmail(): string{
    return $this->email;
  }
  
  public function getEmailSafe(): string{
    return protect($this->getEmail());
  }
}

?>
