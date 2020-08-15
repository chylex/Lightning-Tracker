<?php
declare(strict_types = 1);

namespace Database\Filters\Conditions;

use Database\Filters\AbstractFilter;
use Database\Filters\IWhereCondition;
use PDOStatement;
use function Database\bind;

final class FieldLike implements IWhereCondition{
  private ?string $table_name;
  private string $field;
  private string $value;
  
  public function __construct(string $field, string $value, ?string $table_name = null){
    $this->field = $field;
    $this->value = $value;
    $this->table_name = $table_name;
  }
  
  public function getSql(): string{
    $collate = DB_SUPPORTS_UTF8MB4_0900_AI_CI ? ' COLLATE utf8mb4_0900_ai_ci' : '';
    return AbstractFilter::field($this->table_name, $this->field)."$collate LIKE CONCAT('%', :$this->field, '%')";
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    bind($stmt, $this->field, $this->value);
  }
}

?>
