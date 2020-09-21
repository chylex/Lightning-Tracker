<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class CompositeComponent implements IViewable{
  public static function nonNull(...$components): CompositeComponent{
    return new CompositeComponent(...array_filter($components, fn($v): bool => $v !== null));
  }
  
  /**
   * @var IViewable[]
   */
  private array $components;
  
  public function __construct(...$components){
    $this->components = $components;
  }
  
  public function echoBody(): void{
    foreach($this->components as $component){
      $component->echoBody();
    }
  }
}

?>
