<?php
declare(strict_types = 1);

namespace Pages\Components\Issues;

use Pages\IViewable;

abstract class AbstractIssueTag implements IIssueTag, IViewable{
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
  
  /** @noinspection PhpUnused */
  public static abstract function init(): void;
  /** @noinspection PhpUnused */
  public static abstract function get(string $id): self;
  /** @noinspection PhpUnused */
  public static abstract function exists(string $id): bool;
  
  /**
   * @return self[]
   * @noinspection PhpUnused
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
  
  public final function getId(): string{
    return $this->id;
  }
  
  public final function getTitle(): string{
    return $this->title;
  }
  
  public final function getTagClass(): string{
    return 'issue-tag issue-'.$this->kind.'-'.$this->id;
  }
  
  public final function echoBody(): void{
    echo <<<HTML
<span class="issue-tag issue-$this->kind-$this->id"> $this->title</span>
HTML;
  }
}

?>
