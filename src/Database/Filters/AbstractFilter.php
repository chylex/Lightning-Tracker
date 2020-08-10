<?php
declare(strict_types = 1);

namespace Database\Filters;

use LogicException;
use PDOStatement;

abstract class AbstractFilter{
  protected const OP_EQ = 'eq';
  protected const OP_LIKE = 'like';
  
  protected const ORDER_ASC = 'ASC';
  protected const ORDER_DESC = 'DESC';
  
  public static abstract function empty(): self;
  
  private ?int $limit_offset = null;
  private ?int $limit_count = null;
  
  /**
   * @param int $offset
   * @param int $count
   * @return $this
   */
  public function limit(int $offset, int $count): self{
    $this->limit_offset = $offset;
    $this->limit_count = $count;
    return $this;
  }
  
  /**
   * @param Pagination $pagination
   * @return $this
   */
  public function page(Pagination $pagination): self{
    return $this->limit(($pagination->getCurrentPage() - 1) * $pagination->getElementsPerPage(), $pagination->getElementsPerPage());
  }
  
  protected abstract function getWhereColumns(): array;
  protected abstract function getOrderByColumns(): array;
  
  public abstract function prepareStatement(PDOStatement $stmt): void;
  
  public final function injectClauses(string $sql, ?string $table_name = null): string{
    $clauses = [
        '# WHERE' => ['WHERE', $this->generateWhereClause($table_name)],
        '# ORDER' => ['ORDER BY', $this->generateOrderByClause($table_name)],
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
  
  public final function generateClauses(bool $is_count_query = false, ?string $table_name = null): string{
    $clauses = $is_count_query ? [
        'WHERE' => $this->generateWhereClause($table_name)
    ] : [
        'WHERE'    => $this->generateWhereClause($table_name),
        'ORDER BY' => $this->generateOrderByClause($table_name),
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
  
  protected function generateOrderByClause(?string $table_name): string{
    $cols = [];
    
    foreach($this->getOrderByColumns() as $field => $type){
      if (!$type){
        continue;
      }
      
      switch($type){
        case self::ORDER_ASC:
        case self::ORDER_DESC:
          $cols[] = self::field($table_name, $field).' '.$type;
          break;
        
        default:
          throw new LogicException("Invalid sort direction '$type'.");
      }
    }
    
    return empty($cols) ? '' : implode(', ', $cols);
  }
  
  protected function generateLimitClause(): string{
    return $this->limit_offset === null ? '' : (int)$this->limit_offset.', '.(int)$this->limit_count;
  }
  
  protected static final function field(?string $table_name, string $field_name): string{
    return $table_name === null ? "`$field_name`" : "`$table_name`.`$field_name`";
  }
}

?>
