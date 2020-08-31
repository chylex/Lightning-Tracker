<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class TitledSectionComponent implements IViewable{
  public static function wrap(string $title, ?IViewable $component): ?IViewable{
    return $component === null ? null : new TitledSectionComponent($title, $component);
  }
  
  private string $title;
  private IViewable $component;
  
  public function __construct(string $title, IViewable $component){
    $this->title = $title;
    $this->component = $component;
  }
  
  public function echoBody(): void{
    echo '<h3>';
    echo $this->title;
    echo '</h3><article>';
    $this->component->echoBody();
    echo '</article>';
  }
}

?>
