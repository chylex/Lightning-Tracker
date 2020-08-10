<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractFilter;
use Database\Filters\Sorting;
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
  
  private ?string $name = null;
  private ?string $url = null;
  private ?UserProfile $visible_to = null;
  private bool $visible_to_set = false;
  
  public function name(string $name): self{
    $this->name = $name;
    return $this;
  }
  
  public function url(string $url): self{
    $this->url = $url;
    return $this;
  }
  
  public function visibleTo(?UserProfile $visible_to): self{
    $this->visible_to = $visible_to;
    $this->visible_to_set = true;
    return $this;
  }
  
  protected function getWhereColumns(): array{
    return [
        'name' => $this->name === null ? null : self::OP_LIKE,
        'url'  => $this->url === null ? null : self::OP_LIKE
    ];
  }
  
  protected function generateWhereClause(?string $table_name): string{
    $clause = parent::generateWhereClause($table_name);
    
    if ($this->visible_to_set){
      if (!empty($clause)){
        $clause .= ' AND ';
      }
      
      $clause .= ' ('.self::field($table_name, 'hidden').' = FALSE';
      
      if ($this->visible_to !== null){
        $clause .= self::getUserVisibilityClause($table_name);
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
    bind($stmt, 'name', $this->name);
    bind($stmt, 'url', $this->url);
    
    if ($this->visible_to !== null){
      self::bindUserVisibility($stmt, $this->visible_to);
    }
  }
}

?>
