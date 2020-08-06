<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class IssueUser{
  private int $id;
  private string $name;
  
  public function __construct(int $id, string $name){
    $this->id = $id;
    $this->name = $name;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getNameSafe(): string{
    return protect($this->name);
  }
}

?>
