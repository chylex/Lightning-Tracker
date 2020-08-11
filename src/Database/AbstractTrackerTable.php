<?php
declare(strict_types = 1);

namespace Database;

use Database\Filters\AbstractTrackerIdFilter;
use Database\Objects\TrackerInfo;
use PDO;

abstract class AbstractTrackerTable extends AbstractTable{
  private int $tracker_id;
  
  public function __construct(PDO $db, TrackerInfo $tracker){
    parent::__construct($db);
    $this->tracker_id = $tracker->getId();
  }
  
  protected function getTrackerId(): int{
    return $this->tracker_id;
  }
  
  protected function prepareFilter(AbstractTrackerIdFilter $filter, ?string $table_name = null): AbstractTrackerIdFilter{
    return $filter->internalSetTracker($this->getTrackerId(), $table_name);
  }
}

?>
