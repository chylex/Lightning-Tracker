<?php
declare(strict_types = 1);

namespace Database\Filters;

use LogicException;
use Routing\Request;

class Sorting{
  public const GET_SORT = 'sort';
  
  private const KEY_ASC = 'a';
  private const KEY_DESC = 'd';
  
  public const SQL_ASC = 'ASC';
  public const SQL_DESC = 'DESC';
  
  private static function keyToSql(string $key): ?string{
    switch($key){
      case self::KEY_ASC:
        return self::SQL_ASC;
      
      case self::KEY_DESC:
        return self::SQL_DESC;
    }
    
    return null;
  }
  
  private static function sqlToKey(string $sql): string{
    switch($sql){
      case self::SQL_ASC:
        return self::KEY_ASC;
      
      case self::SQL_DESC:
        return self::KEY_DESC;
      
      default:
        throw new LogicException('Invalid SQL order direction.');
    }
  }
  
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
    
    foreach(explode(',', $rule_str) as $rule){
      $colon = mb_strpos($rule, ':');
      $direction = null;
      
      if ($colon === false){
        $direction = self::SQL_ASC;
      }
      else{
        $direction = self::keyToSql(mb_substr($rule, $colon + 1));
        $rule = mb_substr($rule, 0, $colon);
      }
      
      if ($direction !== null && in_array($rule, $columns)){
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
      $new_rules_str[] = $column.':'.self::sqlToKey($direction);
    }
    
    return $this->req->pathWithGet(self::GET_SORT, empty($new_rules_str) ? null : implode(',', $new_rules_str));
  }
}

?>
