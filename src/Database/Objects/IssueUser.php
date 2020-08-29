<?php
declare(strict_types = 1);

namespace Database\Objects;

use Data\UserId;

final class IssueUser{
  private string $id;
  private string $name;
  
  public function __construct(string $id, string $name){
    $this->id = $id;
    $this->name = $name;
  }
  
  public function getId(): UserId{
    return UserId::fromRaw($this->id);
  }
  
  public function getName(): string{
    return $this->name;
  }
}

?>
