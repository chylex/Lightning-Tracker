<?php
declare(strict_types = 1);

namespace Pages\Components\Markdown;

use Pages\IViewable;

final class MarkdownParseResult implements IViewable{
  private string $html;
  private int $checkboxes;
  
  public function __construct(string $html, int $checkboxes){
    $this->html = $html;
    $this->checkboxes = $checkboxes;
  }
  
  public function hasCheckboxes(): bool{
    return $this->checkboxes > 0;
  }
  
  public function echoBody(): void{
    echo $this->html;
  }
}

?>
