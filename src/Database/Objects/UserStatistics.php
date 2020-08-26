<?php
declare(strict_types = 1);

namespace Database\Objects;

final class UserStatistics{
  private int $project_membership_count;
  private int $issues_created_count;
  private int $issues_assigned_count;
  
  public function __construct(int $project_membership_count, int $issues_created_count, int $issues_assigned_count){
    $this->project_membership_count = $project_membership_count;
    $this->issues_created_count = $issues_created_count;
    $this->issues_assigned_count = $issues_assigned_count;
  }
  
  public function getProjectMembershipCount(): int{
    return $this->project_membership_count;
  }
  
  public function getIssuesCreatedCount(): int{
    return $this->issues_created_count;
  }
  
  public function getIssuesAssignedCount(): int{
    return $this->issues_assigned_count;
  }
}

?>
