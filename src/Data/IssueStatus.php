<?php
declare(strict_types = 1);

namespace Data;

use Pages\Components\Issues\AbstractIssueTag;

final class IssueStatus extends AbstractIssueTag{
  public const OPEN = 'open';
  public const IN_PROGRESS = 'in-progress';
  public const READY_TO_TEST = 'ready-to-test';
  public const BLOCKED = 'blocked';
  public const FINISHED = 'finished';
  public const REJECTED = 'rejected';
  
  private static array $all;
  private static self $unknown;
  
  public static function init(): void{
    self::$all = self::setup([new IssueStatus(self::OPEN, 'Open'),
                              new IssueStatus(self::IN_PROGRESS, 'In Progress'),
                              new IssueStatus(self::READY_TO_TEST, 'Ready To Test'),
                              new IssueStatus(self::BLOCKED, 'Blocked'),
                              new IssueStatus(self::FINISHED, 'Finished'),
                              new IssueStatus(self::REJECTED, 'Rejected')]);
    
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
