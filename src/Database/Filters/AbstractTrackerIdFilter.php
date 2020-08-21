<?php
declare(strict_types = 1);

namespace Database\Filters;

use PDO;
use PDOStatement;

abstract class AbstractTrackerIdFilter extends AbstractFilter{
  private int $tracker_id;
  private ?string $tracker_id_prefix = null;
  
  public function internalSetTracker(int $tracker_id, ?string $table_name): self{
    $this->tracker_id = $tracker_id;
    $this->tracker_id_prefix = $table_name;
    return $this;
  }
  
  protected function generateWhereConditions(): array{
    $conditions = parent::generateWhereConditions();
    
    $conditions[] = new class($this->tracker_id_prefix, $this->tracker_id) implements IWhereCondition{
      private ?string $tracker_id_prefix;
      private int $tracker_id;
      
      public function __construct(?string $tracker_id_prefix, int $tracker_id){
        $this->tracker_id_prefix = $tracker_id_prefix;
        $this->tracker_id = $tracker_id;
      }
      
      public function getSql(): string{
        return Field::sql('tracker_id', $this->tracker_id_prefix).' = :tracker_id';
      }
      
      public function prepareStatement(PDOStatement $stmt): void{
        bind($stmt, 'tracker_id', $this->tracker_id, PDO::PARAM_INT);
      }
    };
    
    return $conditions;
  }
}

?>
