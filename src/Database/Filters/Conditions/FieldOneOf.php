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
  private array $values;
  private bool $can_be_null = false;
  
  public function __construct(string $field, array $values, ?string $table_name = null){
    $this->field = $field;
    $this->values = $values;
    $this->table_name = $table_name;
    
    $null_key = array_search(null, $this->values, true);
    
    if ($null_key !== false){
      unset($this->values[$null_key]);
      $this->values = array_values($this->values);
      $this->can_be_null = true;
    }
  }
  
  public function getSql(): string{
    $indices = empty($this->values) ? [] : range(1, count($this->values));
    $param_list = array_map(fn($index): string => ':'.$this->field.'_'.$index, $indices);
    $param_str = implode(', ', $param_list);
    $field_name = AbstractFilter::field($this->table_name, $this->field);
    
    if ($this->can_be_null){
      if (empty($param_str)){
        return "$field_name IS NULL";
      }
      else{
        return "($field_name IS NULL OR $field_name IN ($param_str))";
      }
    }
    else{
      return "$field_name IN ($param_str)";
    }
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    for($i = 0, $count = count($this->values); $i < $count; $i++){
      bind($stmt, $this->field.'_'.($i + 1), $this->values[$i]);
    }
  }
}

?>
