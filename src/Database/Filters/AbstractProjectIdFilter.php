<?php
declare(strict_types = 1);

namespace Database\Filters;

use PDO;
use PDOStatement;

abstract class AbstractProjectIdFilter extends AbstractFilter{
  private int $project_id;
  private ?string $project_id_prefix = null;
  
  public function internalSetProject(int $project_id, ?string $table_name): self{
    $this->project_id = $project_id;
    $this->project_id_prefix = $table_name;
    return $this;
  }
  
  protected function generateWhereConditions(): array{
    $conditions = parent::generateWhereConditions();
    
    $conditions[] = new class($this->project_id, $this->project_id_prefix) implements IWhereCondition{
      private int $project_id;
      private ?string $project_id_prefix;
      
      public function __construct(int $project_id, ?string $project_id_prefix){
        $this->project_id = $project_id;
        $this->project_id_prefix = $project_id_prefix;
      }
      
      public function getSql(): string{
        return Field::sql('project_id', $this->project_id_prefix).' = :project_id';
      }
      
      public function prepareStatement(PDOStatement $stmt): void{
        bind($stmt, 'project_id', $this->project_id, PDO::PARAM_INT);
      }
    };
    
    return $conditions;
  }
}

?>
