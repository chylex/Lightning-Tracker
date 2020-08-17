<?php
declare(strict_types = 1);

namespace Database\Filters;

final class Field{
  public static function sql(string $field_name, ?string $table_name): string{
    return $table_name === null ? "`$field_name`" : "`$table_name`.`$field_name`";
  }
  
  private string $field_name;
  private ?string $table_name;
  
  public function __construct(string $field_name, ?string $table_name = null){
    $this->field_name = $field_name;
    $this->table_name = $table_name;
  }
  
  public function getFieldName(): string{
    return $this->field_name;
  }
  
  public function getSql(): string{
    return self::sql($this->field_name, $this->table_name);
  }
}

?>
