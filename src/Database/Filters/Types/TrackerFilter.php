<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractFilter;
use Database\Filters\General\Sorting;
use Database\Objects\UserProfile;
use PDO;
use PDOStatement;
use function Database\bind;

final class TrackerFilter extends AbstractFilter{
  public static function getUserVisibilityClause(?string $table_name = null): string{
    // TODO have roles which ban the user instead?
    return
        ' OR '.self::field($table_name, 'owner_id').' = :user_id_1'.
        ' OR EXISTS(SELECT 1 FROM tracker_members tm WHERE tm.tracker_id = '.self::field($table_name, 'id').' AND tm.user_id = :user_id_2)';
  }
  
  public static function bindUserVisibility(PDOStatement $stmt, UserProfile $user): void{
    bind($stmt, 'user_id_1', $user->getId(), PDO::PARAM_INT);
    bind($stmt, 'user_id_2', $user->getId(), PDO::PARAM_INT);
  }
  
  public static function empty(): self{
    return new self();
  }
  
  private ?UserProfile $visible_to = null;
  private bool $visible_to_set = false;
  
  public function visibleTo(?UserProfile $visible_to): self{
    $this->visible_to = $visible_to;
    $this->visible_to_set = true;
    return $this;
  }
  
  protected function generateWhereClause(): string{
    $clause = parent::generateWhereClause();
    
    if ($this->visible_to_set){
      if (!empty($clause)){
        $clause .= ' AND ';
      }
      
      $clause .= ' (hidden = FALSE';
      
      if ($this->visible_to !== null){
        $clause .= self::getUserVisibilityClause();
      }
      
      $clause .= ')';
    }
    
    return $clause;
  }
  
  protected function getSortingColumns(): array{
    return [
        'name'
    ];
  }
  
  protected function getDefaultOrderByColumns(): array{
    return [
        'name' => Sorting::SQL_ASC
    ];
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    parent::prepareStatement($stmt);
    
    if ($this->visible_to !== null){
      self::bindUserVisibility($stmt, $this->visible_to);
    }
  }
}

?>
