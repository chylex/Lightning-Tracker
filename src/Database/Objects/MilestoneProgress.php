<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class MilestoneProgress{
  private int $id;
  private string $title;
  private ?int $percentage_done;
  
  public function __construct(int $id, string $title, ?int $percentage_done){
    $this->id = $id;
    $this->title = $title;
    $this->percentage_done = $percentage_done;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getTitleSafe(): string{
    return protect($this->title);
  }
  
  public function getPercentageDone(): ?int{
    return $this->percentage_done;
  }
}

?>
