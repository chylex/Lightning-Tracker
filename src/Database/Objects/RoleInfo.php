<?php
declare(strict_types = 1);

namespace Database\Objects;

final class RoleInfo{
  public const SYSTEM_NORMAL = 'normal';
  public const SYSTEM_ADMIN = 'admin';
  
  public const PROJECT_NORMAL = 'normal';
  public const PROJECT_OWNER = 'owner';
  
  private int $id;
  private string $type;
  private string $title;
  private int $ordering;
  
  public function __construct(int $id, string $type, string $title, int $ordering){
    $this->id = $id;
    $this->type = $type;
    $this->title = $title;
    $this->ordering = $ordering;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getType(): string{
    return $this->type;
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
}

?>
