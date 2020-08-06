<?php
declare(strict_types = 1);

namespace Pages\Components\Issues;

final class IssueScale extends AbstractIssueTag{
  private static array $all;
  private static self $unknown;
  
  public static function init(): void{
    self::$all = self::setup([new IssueScale('tiny', 'Tiny'),
                              new IssueScale('small', 'Small'),
                              new IssueScale('medium', 'Medium'),
                              new IssueScale('large', 'Large'),
                              new IssueScale('massive', 'Massive')]);
    
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
