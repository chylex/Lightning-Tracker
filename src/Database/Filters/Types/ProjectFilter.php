<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Data\UserId;
use Database\Filters\AbstractFilter;
use Database\Filters\Conditions\FieldLike;
use Database\Filters\Field;
use Database\Filters\General\Filtering;
use Database\Filters\General\Sorting;
use Database\Filters\IWhereCondition;
use PDOStatement;

final class ProjectFilter extends AbstractFilter{
  public static function getUserVisibilityClause(?string $table_name = null): string{
    return
        ' OR '.Field::sql('owner_id', $table_name).' = :user_id_1'.
        ' OR EXISTS(SELECT 1 FROM project_members pm WHERE pm.project_id = '.Field::sql('id', $table_name).' AND pm.user_id = :user_id_2)';
  }
  
  public static function bindUserVisibility(PDOStatement $stmt, UserId $user_id): void{
    bind($stmt, 'user_id_1', $user_id);
    bind($stmt, 'user_id_2', $user_id);
  }
  
  public static function empty(): self{
    return new self();
  }
  
  private ?UserId $visible_to = null;
  private bool $visible_to_set = false;
  
  public function visibleTo(?UserId $visible_to): self{
    $this->visible_to = $visible_to;
    $this->visible_to_set = true;
    return $this;
  }
  
  protected function generateWhereConditions(): array{
    $conditions = parent::generateWhereConditions();
    
    if ($this->visible_to_set){
      $conditions[] = new class($this->visible_to) implements IWhereCondition{
        private ?UserId $visible_to;
        
        public function __construct(?UserId $visible_to){
          $this->visible_to = $visible_to;
        }
        
        public function getSql(): string{
          $clause = '(hidden = FALSE';
          
          if ($this->visible_to !== null){
            $clause .= ProjectFilter::getUserVisibilityClause();
          }
          
          return $clause.')';
        }
        
        public function prepareStatement(PDOStatement $stmt): void{
          if ($this->visible_to !== null){
            ProjectFilter::bindUserVisibility($stmt, $this->visible_to);
          }
        }
      };
    }
    
    return $conditions;
  }
  
  protected function getFilteringColumns(): array{
    return [
        'name' => Filtering::TYPE_TEXT,
        'url'  => Filtering::TYPE_TEXT,
    ];
  }
  
  protected function getFilterWhereCondition(string $field, $value): ?IWhereCondition{
    switch($field){
      case 'name':
      case 'url':
        return new FieldLike($field, $value);
      
      default:
        return null;
    }
  }
  
  protected function getSortingFields(): array{
    return [
        new Field('name'),
    ];
  }
  
  protected function getDefaultSortingRuleList(): array{
    return [
        (new Field('name'))->sortRule(Sorting::SQL_ASC),
    ];
  }
}

?>
