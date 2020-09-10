<?php
declare(strict_types = 1);

namespace Database\Filters;

use Database\Filters\General\Filtering;
use Database\Filters\General\Pagination;
use Database\Filters\General\Sorting;
use LogicException;
use PDO;
use PDOStatement;
use Routing\Request;

abstract class AbstractFilter{
  public const STMT_SELECT_APPEND = 0;
  public const STMT_SELECT_INJECT = 1;
  public const STMT_COUNT = 2;
  
  /** @noinspection PhpUnused */
  public static abstract function empty(): self;
  
  private ?Filtering $filtering = null;
  private ?Sorting $sorting = null;
  private ?Pagination $pagination = null;
  
  public function isEmpty(): bool{
    $f = $this->filtering;
    $s = $this->sorting;
    return ($f === null || $f->isEmpty()) && ($s === null || $s->isEmpty()) && $this->pagination === null;
  }
  
  public function filter(): Filtering{
    $this->filtering = Filtering::fromGlobals($this->getFilteringColumns());
    return $this->filtering;
  }
  
  public function filterManual(array $values): Filtering{
    $this->filtering = Filtering::fromArray($values, $this->getFilteringColumns());
    return $this->filtering;
  }
  
  public function sort(Request $req): Sorting{
    $this->sorting = Sorting::fromGlobals($req, $this->getSortingFields());
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
   * @noinspection PhpUnusedParameterInspection
   */
  protected function getFilterWhereCondition(string $field, $value): ?IWhereCondition{
    return null;
  }
  
  protected function getFilteringColumns(): array{
    return [];
  }
  
  /**
   * @return Field[]
   */
  protected function getSortingFields(): array{
    return [];
  }
  
  protected abstract function getDefaultSortingRuleList(): array;
  
  public final function prepare(PDO $db, string $sql, int $type = self::STMT_SELECT_APPEND): PDOStatement{
    $conditions = $this->generateWhereConditions();
    $where = implode(' AND ', array_map(fn($condition): string => $condition->getSql(), $conditions));
    
    if ($type === self::STMT_SELECT_INJECT){
      $clauses = [
          '# WHERE' => ['WHERE', $where],
          '# ORDER' => ['ORDER BY', $this->generateOrderByClause()],
          '# LIMIT' => ['LIMIT', $this->generateLimitClause()]
      ];
      
      $sql = str_replace("\r", '', $sql);
      
      foreach($clauses as $comment => [$name, $contents]){
        $replacement = empty($contents) ? '' : $name.' '.$contents;
        
        $count = 0;
        $sql = preg_replace('/^'.$comment.'$/m', $replacement, $sql, -1, $count);
        
        if ($count !== 1){
          throw new LogicException('Invalid amount of SQL clause "'.$comment.'" comments ('.$count.').');
        }
      }
    }
    else{
      $clauses = $type === self::STMT_COUNT ? [
          'WHERE' => $where
      ] : [
          'WHERE'    => $where,
          'ORDER BY' => $this->generateOrderByClause(),
          'LIMIT'    => $this->generateLimitClause()
      ];
      
      foreach($clauses as $name => $contents){
        if (!empty($contents)){
          $sql .= " $name $contents";
        }
      }
    }
    
    $stmt = $db->prepare($sql);
    
    foreach($conditions as $condition){
      $condition->prepareStatement($stmt);
    }
    
    return $stmt;
  }
  
  /**
   * @return IWhereCondition[]
   */
  protected function generateWhereConditions(): array{
    $conditions = [];
    
    if ($this->filtering !== null){
      foreach($this->filtering->getRules() as $field => $value){
        $condition = $this->getFilterWhereCondition($field, $value);
        
        if ($condition !== null){
          $conditions[] = $condition;
        }
      }
    }
    
    return $conditions;
  }
  
  private function generateOrderByClause(): string{
    $cols = [];
    
    if ($this->sorting === null || $this->sorting->isEmpty()){
      $rules = $this->getDefaultSortingRuleList();
    }
    else{
      $rules = array_merge($this->sorting->getRuleList(), $this->getDefaultSortingRuleList());
    }
    
    foreach($rules as [/** @var Field $field */ $field, $direction]){
      if (!$direction){
        continue;
      }
      
      switch($direction){
        case Sorting::SQL_ASC:
        case Sorting::SQL_DESC:
          $cols[] = $field->getSql().' '.$direction;
          break;
        
        default:
          throw new LogicException("Invalid sort direction '$direction'.");
      }
    }
    
    return empty($cols) ? '' : implode(', ', $cols);
  }
  
  private function generateLimitClause(): string{
    if ($this->pagination === null){
      return '';
    }
    
    $limit_offset = ($this->pagination->getCurrentPage() - 1) * $this->pagination->getElementsPerPage();
    $limit_count = $this->pagination->getElementsPerPage();
    
    return $limit_offset.', '.$limit_count;
  }
}

?>
