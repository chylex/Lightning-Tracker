<?php
declare(strict_types = 1);

namespace Database\Filters;

class Filtering{
  public const GET_FILTER = 'filter';
  
  public const TYPE_TEXT = 0;
  public const TYPE_MULTISELECT = 1;
  
  public const RULE_SEPARATOR = '.';
  public const KEY_VALUE_SEPARATOR = '~';
  public const MULTISELECT_SEPARATOR = '+';
  
  public static function encode(string $str): string{
    return str_replace(['.', '+', '~'],
                       ['%2E', '%2B', '%7E'],
                       rawurlencode($str));
  }
  
  public static function decode(string $str): string{
    return rawurldecode(str_replace(['%2E', '%2B', '%7E'],
                                    ['.', '+', '~'],
                                    $str));
  }
  
  /**
   * @param string[] $fields
   * @return Filtering
   */
  public static function fromGlobals(array $fields): Filtering{
    $rule_str = $_GET[self::GET_FILTER] ?? '';
    $rules = [];
    
    if (empty($rule_str)){
      return new Filtering($fields, $rules);
    }
    
    foreach(explode(self::RULE_SEPARATOR, $rule_str) as $rule){
      if (empty($rule)){
        continue;
      }
      
      $pos = mb_strpos($rule, self::KEY_VALUE_SEPARATOR);
      
      if ($pos === false){
        continue;
      }
      
      $key = self::decode(mb_substr($rule, 0, $pos));
      $value_raw = mb_substr($rule, $pos + 1);
      $value_type = $fields[$key] ?? null;
      
      switch($value_type){
        case self::TYPE_TEXT:
          $rules[$key] = self::decode($value_raw);
          break;
        
        case self::TYPE_MULTISELECT:
          $rules[$key] = array_map(fn($v): string => self::decode($v), explode(self::MULTISELECT_SEPARATOR, $value_raw));
          break;
      }
    }
    
    return new Filtering($fields, $rules);
  }
  
  /**
   * @var string[] List of valid fields.
   */
  private array $fields;
  
  /**
   * @var array Mapping of a subset of valid fields to arrays of values.
   */
  private array $rules;
  
  private function __construct(array $fields, array $rules){
    $this->fields = $fields;
    $this->rules = $rules;
  }
  
  public function isFilterable(string $field): bool{
    return isset($this->fields[$field]);
  }
  
  /**
   * @param string $field
   * @param int $type
   * @return mixed|null
   */
  public function getFilter(string $field, int $type){
    $rule = $this->rules[$field] ?? null;
    return $rule === null || $this->fields[$field] !== $type ? null : $rule;
  }
  
  public function getRules(): array{
    return $this->rules;
  }
}

?>
