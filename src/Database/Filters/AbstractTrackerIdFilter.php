<?php
declare(strict_types = 1);

namespace Database\Filters;

use Database\Objects\TrackerInfo;
use PDO;
use PDOStatement;
use function Database\bind;

abstract class AbstractTrackerIdFilter extends AbstractFilter{
  private ?TrackerInfo $tracker = null;
  
  public function tracker(TrackerInfo $tracker): self{
    $this->tracker = $tracker;
    return $this;
  }
  
  protected function getWhereColumns(): array{
    return [
        'tracker_id' => $this->tracker === null ? null : self::OP_EQ
    ];
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    if ($this->tracker !== null){
      bind($stmt, 'tracker_id', $this->tracker->getId(), PDO::PARAM_INT);
    }
  }
}

?>
