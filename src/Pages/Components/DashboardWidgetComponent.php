<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class DashboardWidgetComponent implements IViewable{
  private string $title;
  private int $size;
  private IViewable $component;
  
  public function __construct(string $title, int $size, IViewable $component){
    $this->title = $title;
    $this->size = $size;
    $this->component = $component;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    echo <<<HTML
<div class="dashboard-panel dashboard-panel-{$this->size}x">
  <h3>$this->title</h3>
  <article>
HTML;
    
    $this->component->echoBody();
    
    echo <<<HTML
  </article>
</div>
HTML;
  }
}

?>
