<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class RoleInfo{
  private int $id;
  private string $title;
  private bool $special;
  
  public function __construct(int $id, string $title, bool $special){
    $this->id = $id;
    $this->title = $title;
    $this->special = $special;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getTitle(): string{
    return $this->title;
  }
  
  public function getTitleSafe(): string{
    return protect($this->title);
  }
  
  public function isSpecial(): bool{
    return $this->special;
  }
}

?>
