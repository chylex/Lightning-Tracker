<?php
declare(strict_types = 1);

namespace Database\Filters;

use LogicException;
use Routing\Request;

class Sorting{
  public const GET_SORT = 'sort';
  
  public const SQL_ASC = 'ASC';
  public const SQL_DESC = 'DESC';
  
  private const RULE_SEPARATOR = '.';
  private const REVERSE_DIRECTION_CHAR = '~';
  
  /**
   * @param Request $req
   * @param string[] $columns
   * @return Sorting
   */
  public static function fromGlobals(Request $req, array $columns): Sorting{
    $rule_str = $_GET[self::GET_SORT] ?? '';
    $rules = [];
    
    if (empty($rule_str)){
      return new Sorting($req, $columns, $rules);
    }
    
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
      
      if (in_array($rule, $columns)){
        $rules[$rule] = $direction;
      }
    }
    
    return new Sorting($req, $columns, $rules);
  }
  
  private Request $req;
  
  /**
   * @var string[] List of valid columns.
   */
  private array $columns;
  
  /**
   * @var string[] Mapping of a subset of valid columns to SQL order directions (ASC/DESC).
   */
  private array $rules;
  
  private function __construct(Request $req, array $columns, array $rules){
    $this->req = $req;
    $this->columns = $columns;
    $this->rules = $rules;
  }
  
  public function isSortable(string $column): bool{
    return in_array($column, $this->columns, true);
  }
  
  public function getSortDirection(string $column): ?string{
    return $this->rules[$column] ?? null;
  }
  
  public function getRules(): array{
    return $this->rules;
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
    
    foreach($new_rules as $column => $direction){
      $new_rules_str[] = ($direction === self::SQL_DESC ? self::REVERSE_DIRECTION_CHAR : '').$column;
    }
    
    return $this->req->pathWithGet(self::GET_SORT, empty($new_rules_str) ? null : implode(self::RULE_SEPARATOR, $new_rules_str));
  }
}

?>
