<?php
declare(strict_types = 1);

namespace Data;

use Pages\Components\Issues\AbstractIssueTag;

final class IssueScale extends AbstractIssueTag{
  public const TINY = 'tiny';
  public const SMALL = 'small';
  public const MEDIUM = 'medium';
  public const LARGE = 'large';
  public const MASSIVE = 'massive';
  
  private static array $all;
  private static self $unknown;
  
  public static function init(): void{
    self::$all = self::setup([new IssueScale(self::TINY, 'Tiny'),
                              new IssueScale(self::SMALL, 'Small'),
                              new IssueScale(self::MEDIUM, 'Medium'),
                              new IssueScale(self::LARGE, 'Large'),
                              new IssueScale(self::MASSIVE, 'Massive')]);
    
    self::$unknown = new IssueScale('unknown', 'Unknown');
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
    parent::__construct('scale', $id, $title);
  }
}

IssueScale::init();

?>
