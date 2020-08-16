<?php
declare(strict_types = 1);

namespace Database\Filters\Conditions;

use Database\Filters\AbstractFilter;
use Database\Filters\IWhereCondition;
use PDOStatement;
use function Database\bind;

final class FieldOneOf implements IWhereCondition{
  private ?string $table_name;
  private string $field;
  
  /**
   * @var string[]
   */
  private array $values;
  
  public function __construct(string $field, array $values, ?string $table_name = null){
    $this->field = $field;
    $this->values = $values;
    $this->table_name = $table_name;
  }
  
  public function getFieldName(): string{
    return AbstractFilter::field($this->table_name, $this->field);
  }
  
  public function getSql(): string{
    $indices = empty($this->values) ? [] : range(1, count($this->values));
    $param_list = array_map(fn($index): string => ':'.$this->field.'_'.$index, $indices);
    $param_str = implode(', ', $param_list);
    
    return $this->getFieldName().' IN ('.$param_str.')';
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    for($i = 0, $count = count($this->values); $i < $count; $i++){
      bind($stmt, $this->field.'_'.($i + 1), $this->values[$i]);
    }
  }
}

?>
