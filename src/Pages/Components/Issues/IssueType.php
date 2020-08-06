<?php
declare(strict_types = 1);

namespace Pages\Components\Issues;

use Pages\Components\Text;
use Pages\IViewable;

final class IssueType{
  private static array $all;
  private static self $unknown;
  
  public static function init(): void{
    $types = [new IssueType('feature', 'Feature', 'leaf'),
              new IssueType('enhancement', 'Enhancement', 'wand'),
              new IssueType('bug', 'Bug', 'bug'),
              new IssueType('crash', 'Crash', 'fire'),
              new IssueType('task', 'Task', 'clock')];
    
    self::$all = [];
    
    foreach($types as $type){
      self::$all[$type->id] = $type;
    }
    
    self::$unknown = new IssueType('unknown', 'Unknown', 'warning');
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
  
  private string $id;
  private string $title;
  private string $icon;
  
  private function __construct(string $id, string $title, string $icon){
    $this->id = $id;
    $this->title = $title;
    $this->icon = $icon;
  }
  
  public function getId(): string{
    return $this->id;
  }
  
  public function getTitle(): string{
    return $this->title;
  }
  
  public function getIcon(): string{
    return $this->icon;
  }
  
  public function getViewable(bool $icon_only): IViewable{
    if ($icon_only){
      $html = <<<HTML
<span class="icon icon-$this->icon faded" title="$this->title"></span>
HTML;
    }
    else{
      $html = <<<HTML
<span class="icon icon-$this->icon faded"></span> $this->title
HTML;
    }
    
    return Text::plain($html); // TODO perhaps not entirely clear
  }
}

IssueType::init();

?>
