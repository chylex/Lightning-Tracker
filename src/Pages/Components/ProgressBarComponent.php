<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class ProgressBarComponent implements IViewable{
  public static function echoHead(): void{
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/progressbar.css">
HTML;
  }
  
  private ?int $progress;
  private bool $compact = false;
  
  public function __construct(?int $progress){
    $this->progress = $progress;
  }
  
  public function compact(): self{
    $this->compact = true;
    return $this;
  }
  
  public function echoBody(): void{
    $value = $this->progress ?? 0;
    $text = $this->progress === null ? '&ndash;' : $this->progress.'%';
    
    $compact_class = $this->compact ? ' compact' : '';
    
    echo <<<HTML
<div class="progress-bar$compact_class">
  <div class="value" style="width:$value%;" data-value="$value">
    <div class="stripes"></div>
  </div>
  <span>$text</span>
</div>
HTML;
  }
}

?>
