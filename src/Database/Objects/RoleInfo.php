<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class RoleInfo{
  private int $id;
  private string $title;
  
  public function __construct(int $id, string $title){
    $this->id = $id;
    $this->title = $title;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getTitleSafe(): string{
    return protect($this->title);
  }
}

?>
