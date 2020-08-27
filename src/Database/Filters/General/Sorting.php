<?php
declare(strict_types = 1);

namespace Database\Filters\General;

use Database\Filters\Field;
use LogicException;
use Routing\Link;
use Routing\Request;

final class Sorting{
  public const GET_SORT = 'sort';
  
  public const SQL_ASC = 'ASC';
  public const SQL_DESC = 'DESC';
  
  private const RULE_SEPARATOR = '.';
  private const REVERSE_DIRECTION_CHAR = '~';
  
  /**
   * @param Request $req
   * @param Field[] $fields
   * @return Sorting
   */
  public static function fromGlobals(Request $req, array $fields): Sorting{
    $rule_str = $_GET[self::GET_SORT] ?? '';
    $rules = [];
    
    $associative_fields = [];
    
    foreach($fields as $field){
      $associative_fields[$field->getFieldName()] = $field;
    }
    
    if (empty($rule_str)){
      return new Sorting($req, $associative_fields, $rules);
    }
  
    /** @var string $rule */
    foreach(explode(self::RULE_SEPARATOR, $rule_str) as $rule){
      if (empty($rule)){
        continue;
      }
      
      if ($rule[0] === self::REVERSE_DIRECTION_CHAR){
        $direction = self::SQL_DESC;
        $rule = substr($rule, 1);
      }
      else{
        $direction = self::SQL_ASC;
      }
      
      if (array_key_exists($rule, $associative_fields)){
        $rules[$rule] = $direction;
      }
    }
    
    return new Sorting($req, $associative_fields, $rules);
  }
  
  private Request $req;
  
  /**
   * @var Field[] Mapping of valid column names to field objects.
   */
  private array $fields;
  
  /**
   * @var string[] Mapping of a subset of valid column names to SQL order directions (ASC/DESC).
   */
  private array $rules;
  
  private function __construct(Request $req, array $fields, array $rules){
    $this->req = $req;
    $this->fields = $fields;
    $this->rules = $rules;
  }
  
  public function isSortable(string $column): bool{
    return array_key_exists($column, $this->fields);
  }
  
  public function getSortDirection(string $column): ?string{
    return $this->rules[$column] ?? null;
  }
  
  public function getRuleList(): array{
    $list = [];
    
    foreach($this->rules as $column => $direction){
      $list[] = $this->fields[$column]->sortRule($direction);
    }
    
    return $list;
  }
  
  public function isEmpty(): bool{
    return empty($this->rules);
  }
  
  public function generateCycledLink(string $column): string{
    if (!$this->isSortable($column)){
      throw new LogicException('Cannot cycle a non-sortable column.');
    }
    
    $new_rules = $this->rules;
    $prev_direction = $this->getSortDirection($column);
    
    if ($prev_direction === null){
      $new_rules[$column] = self::SQL_ASC;
    }
    elseif ($prev_direction === self::SQL_ASC){
      $new_rules[$column] = self::SQL_DESC;
    }
    else{
      unset($new_rules[$column]);
    }
    
    $new_rules_str = [];
    
    foreach($new_rules as $col => $direction){
      $new_rules_str[] = ($direction === self::SQL_DESC ? self::REVERSE_DIRECTION_CHAR : '').$col;
    }
    
    return Link::withGet($this->req, self::GET_SORT, empty($new_rules_str) ? null : implode(self::RULE_SEPARATOR, $new_rules_str));
  }
}

?>
