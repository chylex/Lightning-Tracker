<?php
declare(strict_types = 1);

namespace Database\Filters;

use Database\Filters\General\Filtering;
use Database\Filters\General\Pagination;
use Database\Filters\General\Sorting;
use LogicException;
use PDOStatement;
use Routing\Request;

abstract class AbstractFilter{
  public static abstract function empty(): self;
  
  private ?Filtering $filtering = null;
  private ?Sorting $sorting = null;
  private ?Pagination $pagination = null;
  
  /**
   * @var IWhereCondition[]
   */
  private array $current_where_conditions = []; // TODO ugly
  
  public function isEmpty(): bool{
    $f = $this->filtering;
    $s = $this->sorting;
    return ($f === null || $f->isEmpty()) && ($s === null || $s->isEmpty()) && $this->pagination === null;
  }
  
  public function filter(): Filtering{
    $this->filtering = Filtering::fromGlobals($this->getFilteringColumns());
    return $this->filtering;
  }
  
  public function sort(Request $req): Sorting{
    $this->sorting = Sorting::fromGlobals($req, $this->getSortingColumns());
    return $this->sorting;
  }
  
  public function page(int $total_elements): Pagination{
    $this->pagination = Pagination::fromGlobals($total_elements);
    return $this->pagination;
  }
  
  /**
   * @param string $field
   * @param mixed $value
   * @return IWhereCondition|null
   */
  protected function getFilterWhereCondition(string $field, $value): ?IWhereCondition{
    return null;
  }
  
  protected function getFilteringColumns(): array{
    return [];
  }
  
  protected function getSortingColumns(): array{
    return [];
  }
  
  protected function getDefaultWhereColumns(): array{
    return [];
  }
  
  protected abstract function getDefaultOrderByColumns(): array;
  
  public function prepareStatement(PDOStatement $stmt): void{
    foreach($this->current_where_conditions as $condition){
      $condition->prepareStatement($stmt);
    }
  }
  
  public final function injectClauses(string $sql): string{
    $clauses = [
        '# WHERE' => ['WHERE', $this->generateWhereClause()],
        '# ORDER' => ['ORDER BY', $this->generateOrderByClause()],
        '# LIMIT' => ['LIMIT', $this->generateLimitClause()]
    ];
    
    $sql = str_replace("\r", '', $sql);
    
    foreach($clauses as $comment => $data){
      $name = $data[0];
      $contents = $data[1];
      $replacement = empty($contents) ? '' : $name.' '.$contents;
      
      $count = 0;
      $sql = preg_replace('/^'.$comment.'$/m', $replacement, $sql, -1, $count);
      
      if ($count !== 1){
        throw new LogicException('Invalid amount of SQL clause "'.$comment.'" comments ('.$count.').');
      }
    }
    
    return $sql;
  }
  
  public final function generateClauses(bool $is_count_query = false): string{
    $clauses = $is_count_query ? [
        'WHERE' => $this->generateWhereClause()
    ] : [
        'WHERE'    => $this->generateWhereClause(),
        'ORDER BY' => $this->generateOrderByClause(),
        'LIMIT'    => $this->generateLimitClause()
    ];
    
    $used = [];
    
    foreach($clauses as $name => $contents){
      if (!empty($contents)){
        $used[] = $name.' '.$contents;
      }
    }
    
    return implode(' ', $used);
  }
  
  protected function generateWhereClause(): string{
    $conditions = $this->getDefaultWhereColumns();
    $this->current_where_conditions = [];
    
    if ($this->filtering !== null){
      foreach($this->filtering->getRules() as $field => $value){
        $condition = $this->getFilterWhereCondition($field, $value);
        
        if ($condition !== null){
          $conditions[] = $condition->getSql();
          $this->current_where_conditions[] = $condition;
        }
      }
    }
    
    return implode(' AND ', $conditions);
  }
  
  protected function generateOrderByClause(): string{
    $cols = [];
    $rules = $this->sorting === null || $this->sorting->isEmpty() ? $this->getDefaultOrderByColumns() : $this->sorting->getRules();
    
    foreach($rules as $field => $direction){
      if (!$direction){
        continue;
      }
      
      switch($direction){
        case Sorting::SQL_ASC:
        case Sorting::SQL_DESC:
          $field_period = strpos($field, '.');
          $table_name = $field_period === false ? null : substr($field, 0, $field_period);
          $field_name = $field_period === false ? $field : substr($field, $field_period + 1);
          $cols[] = self::field($table_name, $field_name).' '.$direction;
          break;
        
        default:
          throw new LogicException("Invalid sort direction '$direction'.");
      }
    }
    
    return empty($cols) ? '' : implode(', ', $cols);
  }
  
  protected function generateLimitClause(): string{
    if ($this->pagination === null){
      return '';
    }
    
    $limit_offset = ($this->pagination->getCurrentPage() - 1) * $this->pagination->getElementsPerPage();
    $limit_count = $this->pagination->getElementsPerPage();
    
    return (int)$limit_offset.', '.(int)$limit_count;
  }
  
  public static final function field(?string $table_name, string $field_name): string{
    return $table_name === null ? "`$field_name`" : "`$table_name`.`$field_name`";
  }
}

?>
