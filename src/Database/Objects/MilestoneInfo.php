<?php
declare(strict_types = 1);

namespace Database\Objects;

final class MilestoneInfo{
  private int $milestone_id;
  private string $title;
  private int $ordering;
  
  private int $closed_issues;
  private int $total_issues;
  private ?int $percentage_done;
  private ?string $date_updated;
  
  public function __construct(int $milestone_id, string $title, int $ordering, int $closed_issues, int $total_issues, ?int $percentage_done, ?string $date_updated){
    $this->milestone_id = $milestone_id;
    $this->title = $title;
    $this->ordering = $ordering;
    $this->closed_issues = $closed_issues;
    $this->total_issues = $total_issues;
    $this->percentage_done = $percentage_done;
    $this->date_updated = $date_updated;
  }
  
  public function getMilestoneId(): int{
    return $this->milestone_id;
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
  
  public function getClosedIssues(): int{
    return $this->closed_issues;
  }
  
  public function getTotalIssues(): int{
    return $this->total_issues;
  }
  
  public function getPercentageDone(): ?int{
    return $this->percentage_done;
  }
  
  public function getLastUpdateDate(): ?string{
    return $this->date_updated;
  }
}

?>
