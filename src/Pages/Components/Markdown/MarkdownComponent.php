<?php
declare(strict_types = 1);

namespace Pages\Components\Markdown;

use Pages\IViewable;
use function Database\protect;

final class MarkdownComponent implements IViewable{
  private string $text;
  
  public function __construct(string $text){
    $this->text = $text;
  }
  
  public function getRawTextSafe(): string{
    return protect($this->text);
  }
  
  public function echoBody(): void{
    echo protect($this->text); // TODO
  }
}

?>
