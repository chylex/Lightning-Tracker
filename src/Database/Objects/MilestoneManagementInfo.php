<?php
declare(strict_types = 1);

namespace Database\Objects;

final class MilestoneManagementInfo{
  private MilestoneInfo $milestone_info;
  private int $ordering_limit;
  
  public function __construct(MilestoneInfo $milestone_info, int $ordering_limit){
    $this->milestone_info = $milestone_info;
    $this->ordering_limit = $ordering_limit;
  }
  
  public function getMilestone(): MilestoneInfo{
    return $this->milestone_info;
  }
  
  public function canMoveUp(): bool{
    return $this->milestone_info->getOrdering() !== 1;
  }
  
  public function canMoveDown(): bool{
    return $this->milestone_info->getOrdering() !== $this->ordering_limit;
  }
}

?>
