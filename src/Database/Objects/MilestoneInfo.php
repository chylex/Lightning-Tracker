<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class MilestoneInfo{
  private int $id;
  private string $title;
  private string $date_updated;
  
  private int $closed_issues;
  private int $total_issues;
  private ?int $percentage_done;
  
  public function __construct(int $id, string $title, string $date_updated, int $closed_issues, int $total_issues, ?int $percentage_done){
    $this->id = $id;
    $this->title = $title;
    $this->date_updated = $date_updated;
    $this->closed_issues = $closed_issues;
    $this->total_issues = $total_issues;
    $this->percentage_done = $percentage_done;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getTitleSafe(): string{
    return protect($this->title);
  }
  
  public function getLastUpdateDate(): string{
    return $this->date_updated;
  }
  
  public function getClosedIssues(): int{
    return $this->closed_issues;
  }
  
  public function getTotalIssues(): int{
    return $this->total_issues;
  }
  
  public function getPercentageDone(): ?int{
    return $this->percentage_done;
  }
}

?>
