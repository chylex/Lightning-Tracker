<?php
declare(strict_types = 1);

namespace Database\Filters\Conditions;

use Database\Filters\AbstractFilter;
use Database\Filters\Field;
use Database\Filters\IWhereCondition;
use PDOStatement;
use function Database\bind;

final class FieldLike implements IWhereCondition{
  private Field $field;
  private string $value;
  
  public function __construct(string $field, string $value, ?string $table_name = null){
    $this->field =new Field($field, $table_name);
    $this->value = $value;
  }
  
  public function getSql(): string{
    $field_name = $this->field->getFieldName();
    return $this->field->getSql()." LIKE CONCAT('%', :$field_name, '%')"; // TODO use utf8mb4_0900_ai_ci if available
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    bind($stmt, $this->field->getFieldName(), $this->value);
  }
}

?>
