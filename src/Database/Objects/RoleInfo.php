<?php
declare(strict_types = 1);

namespace Database\Objects;

final class RoleInfo{
  private int $id;
  private string $title;
  private int $ordering;
  private bool $special;
  
  public function __construct(int $id, string $title, int $ordering, bool $special){
    $this->id = $id;
    $this->title = $title;
    $this->ordering = $ordering;
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
  
  public function getOrdering(): int{
    return $this->ordering;
  }
  
  public function isSpecial(): bool{
    return $this->special;
  }
}

?>
