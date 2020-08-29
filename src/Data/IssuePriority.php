<?php
declare(strict_types = 1);

namespace Data;

use Pages\Components\Issues\AbstractIssueTag;

final class IssuePriority extends AbstractIssueTag{
  public const LOW = 'low';
  public const MEDIUM = 'medium';
  public const HIGH = 'high';
  
  private static array $all;
  private static self $unknown;
  
  public static function init(): void{
    self::$all = self::setup([new IssuePriority(self::LOW, 'Low'),
                              new IssuePriority(self::MEDIUM, 'Medium'),
                              new IssuePriority(self::HIGH, 'High')]);
    
    self::$unknown = new IssuePriority('unknown', 'Unknown');
  }
  
  public static function get(string $id): self{
    return self::$all[$id] ?? self::$unknown;
  }
  
  public static function exists(string $id): bool{
    return isset(self::$all[$id]);
  }
  
  /**
   * @return self[]
   */
  public static function list(): array{
    return array_values(self::$all);
  }
  
  private function __construct(string $id, string $title){
    parent::__construct('priority', $id, $title);
  }
}

IssuePriority::init();

?>
