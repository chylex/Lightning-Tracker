<?php
declare(strict_types = 1);

namespace Pages\Components\Issues;

use Pages\IViewable;

abstract class AbstractIssueTag implements IViewable{
  /**
   * @param self[] $items
   * @return array
   */
  protected static function setup(array $items): array{
    $all = [];
    
    foreach($items as $item){
      $all[$item->id] = $item;
    }
    
    return $all;
  }
  
  public static abstract function init(): void;
  public static abstract function get(string $id): self;
  public static abstract function exists(string $id): bool;
  
  /**
   * @return self[]
   */
  public static abstract function list(): array;
  
  private string $kind;
  private string $id;
  private string $title;
  
  public function __construct(string $kind, string $id, string $title){
    $this->kind = $kind;
    $this->id = $id;
    $this->title = $title;
  }
  
  public function getKind(): string{
    return $this->kind;
  }
  
  public function getId(): string{
    return $this->id;
  }
  
  public function getTitle(): string{
    return $this->title;
  }
  
  public function echoBody(): void{
    echo <<<HTML
<span class="issue-tag issue-$this->kind-$this->id"> $this->title</span>
HTML;
  }
}

?>
