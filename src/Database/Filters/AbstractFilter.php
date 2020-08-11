<?php
declare(strict_types = 1);

namespace Database\Filters;

use LogicException;
use PDOStatement;
use Routing\Request;

abstract class AbstractFilter{
  protected const OP_EQ = 'eq';
  protected const OP_LIKE = 'like';
  
  public static abstract function empty(): self;
  
  private ?Filtering $filtering = null;
  private ?Sorting $sorting = null;
  private ?Pagination $pagination = null;
  
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
  
  protected abstract function getWhereColumns(): array;
  
  protected function getFilteringColumns(): array{
    return []; // TODO
  }
  
  protected function getSortingColumns(): array{
    return [];
  }
  
  protected abstract function getDefaultOrderByColumns(): array;
  public abstract function prepareStatement(PDOStatement $stmt): void;
  
  public final function injectClauses(string $sql, ?string $where_table_name = null): string{
    $clauses = [
        '# WHERE' => ['WHERE', $this->generateWhereClause($where_table_name)],
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
  
  public final function generateClauses(bool $is_count_query = false, ?string $where_table_name = null): string{
    $clauses = $is_count_query ? [
        'WHERE' => $this->generateWhereClause($where_table_name)
    ] : [
        'WHERE'    => $this->generateWhereClause($where_table_name),
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
  
  protected function generateWhereClause(?string $table_name): string{
    $cols = [];
    
    foreach($this->getWhereColumns() as $field => $type){
      if (!$type){
        continue;
      }
      
      switch($type){
        case self::OP_EQ:
          $cols[] = self::field($table_name, $field)." = :$field";
          break;
        
        case self::OP_LIKE:
          $cols[] = "`$field` LIKE CONCAT('%', :$field, '%')";
          break;
        
        default:
          throw new LogicException("Invalid filter operator '$type'.");
      }
    }
    
    return empty($cols) ? '' : implode(' AND ', $cols);
  }
  
  protected function generateOrderByClause(): string{
    $cols = [];
    $rules = $this->sorting === null ? $this->getDefaultOrderByColumns() : $this->sorting->getRules();
    
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
  
  protected static final function field(?string $table_name, string $field_name): string{
    return $table_name === null ? "`$field_name`" : "`$table_name`.`$field_name`";
  }
}

?>
