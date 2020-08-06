<?php
declare(strict_types = 1);

namespace Pages\Components\Issues;

final class IssueStatus extends AbstractIssueTag{
  private static array $all;
  private static self $unknown;
  
  public static function init(): void{
    self::$all = self::setup([new IssueStatus('open', 'Open'),
                              new IssueStatus('in-progress', 'In Progress'),
                              new IssueStatus('ready-to-test', 'Ready To Test'),
                              new IssueStatus('blocked', 'Blocked'),
                              new IssueStatus('finished', 'Finished'),
                              new IssueStatus('rejected', 'Rejected')]);
    
    self::$unknown = new IssueStatus('unknown', 'Unknown');
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
    parent::__construct('status', $id, $title);
  }
}

IssueStatus::init();

?>
