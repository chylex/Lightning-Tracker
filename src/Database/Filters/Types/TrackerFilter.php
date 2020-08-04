<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractFilter;
use Database\Objects\UserProfile;
use PDO;
use PDOStatement;
use function Database\bind;

final class TrackerFilter extends AbstractFilter{
  public static function getUserVisibilityClause(): string{
    return ' OR owner = :user_id'; // TODO
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
  
  protected function getOrderByColumns(): array{
    return [
        'id' => self::ORDER_ASC
    ];
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    bind($stmt, 'name', $this->name);
    bind($stmt, 'url', $this->url);
    
    if ($this->visible_to !== null){
      bind($stmt, 'user_id', $this->visible_to->getId(), PDO::PARAM_INT);
    }
  }
}

?>
