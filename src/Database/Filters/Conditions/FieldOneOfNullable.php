<?php
declare(strict_types = 1);

namespace Database\Filters\Conditions;

use Database\Filters\IWhereCondition;
use PDOStatement;

final class FieldOneOfNullable implements IWhereCondition{
  private FieldOneOf $delegate;
  private bool $has_no_values;
  private bool $can_be_null = false;
  
  public function __construct(string $field, array $values, ?string $table_name = null){
    $updated_values = $values;
    $empty_key = array_search('', $updated_values, true);
    
    if ($empty_key !== false){
      unset($updated_values[$empty_key]);
      $updated_values = array_values($updated_values);
      $this->can_be_null = true;
    }
    
    $this->has_no_values = empty($updated_values);
    $this->delegate = new FieldOneOf($field, $updated_values, $table_name);
  }
  
  public function getSql(): string{
    $field = $this->delegate->getFieldSql();
    
    if ($this->can_be_null){
      if ($this->has_no_values){
        return $field.' IS NULL';
      }
      else{
        return '('.$field.' IS NULL OR '.$this->delegate->getSql().')';
      }
    }
    else{
      return $this->delegate->getSql();
    }
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    $this->delegate->prepareStatement($stmt);
  }
}

?>
