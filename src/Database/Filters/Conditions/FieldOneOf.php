<?php
declare(strict_types = 1);

namespace Database\Filters\Conditions;

use Database\Filters\Field;
use Database\Filters\IWhereCondition;
use PDOStatement;

final class FieldOneOf implements IWhereCondition{
  private Field $field;
  
  /**
   * @var string[]
   */
  private array $values;
  
  public function __construct(string $field, array $values, ?string $table_name = null){
    $this->field = new Field($field, $table_name);
    $this->values = $values;
  }
  
  public function getFieldSql(): string{
    return $this->field->getSql();
  }
  
  public function getSql(): string{
    $field_name = $this->field->getFieldName();
    
    $indices = empty($this->values) ? [] : range(1, count($this->values));
    $param_list = array_map(static fn($index): string => ':'.$field_name.'_'.$index, $indices);
    $param_str = implode(', ', $param_list);
    
    return $this->getFieldSql().' IN ('.$param_str.')';
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    $field_name = $this->field->getFieldName();
    
    foreach($this->values as $i => $value){
      bind($stmt, $field_name.'_'.($i + 1), $value);
    }
  }
}

?>
