<?php
declare(strict_types = 1);

namespace Database\Objects;

final class TrackerVisibilityInfo{
  private TrackerInfo $tracker;
  private bool $visible;
  
  public function __construct(TrackerInfo $tracker, bool $visible){
    $this->tracker = $tracker;
    $this->visible = $visible;
  }
  
  public function getTracker(): TrackerInfo{
    return $this->tracker;
  }
  
  public function isVisible(): bool{
    return $this->visible;
  }
}

?>
