<?php
declare(strict_types = 1);

namespace Database\Filters;

use PDO;
use PDOStatement;
use function Database\bind;

abstract class AbstractTrackerIdFilter extends AbstractFilter{
  private int $tracker_id;
  private ?string $tracker_id_prefix = null;
  
  public function internalSetTracker(int $tracker_id, ?string $table_name): self{
    $this->tracker_id = $tracker_id;
    $this->tracker_id_prefix = $table_name;
    return $this;
  }
  
  protected function getDefaultWhereColumns(): array{
    return [
        self::field($this->tracker_id_prefix, 'tracker_id').' = :tracker_id'
    ];
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    parent::prepareStatement($stmt);
    bind($stmt, 'tracker_id', $this->tracker_id, PDO::PARAM_INT);
  }
}

?>
