<?php
declare(strict_types = 1);

namespace Database\Objects;

use Data\UserId;

final class IssueUser{
  private UserId $id;
  private string $name;
  
  public function __construct(UserId $id, string $name){
    $this->id = $id;
    $this->name = $name;
  }
  
  public function getId(): UserId{
    return $this->id;
  }
  
  public function getName(): string{
    return $this->name;
  }
}

?>
