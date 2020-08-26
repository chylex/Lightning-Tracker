<?php
declare(strict_types = 1);

namespace Database\Objects;

final class ProjectVisibilityInfo{
  private ProjectInfo $project;
  private bool $visible;
  
  public function __construct(ProjectInfo $project, bool $visible){
    $this->project = $project;
    $this->visible = $visible;
  }
  
  public function getProject(): ProjectInfo{
    return $this->project;
  }
  
  public function isVisible(): bool{
    return $this->visible;
  }
}

?>
