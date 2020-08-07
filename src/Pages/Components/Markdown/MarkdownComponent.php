<?php
declare(strict_types = 1);

namespace Pages\Components\Markdown;

use Pages\IViewable;
use function Database\protect;

final class MarkdownComponent implements IViewable{
  private string $text;
  private ?string $checkbox_name = null;
  
  public function __construct(string $text){
    $this->text = $text;
  }
  
  public function setCheckboxNameForEditing(string $checkbox_name): MarkdownComponent{
    $this->checkbox_name = $checkbox_name;
    return $this;
  }
  
  public function getRawTextSafe(): string{
    return protect($this->text);
  }
  
  public function parse(): MarkdownParseResult{
    $parser = new MarkdownParser($this->checkbox_name);
    $iter = new UnicodeIterator();
    $lines = mb_split("\n", $this->text);
    
    foreach($lines as $line){
      $iter->prepare($line);
      $parser->parseLine($iter);
    }
    
    return $parser->closeParser();
  }
  
  public function echoBody(): void{
    $this->parse()->echoBody();
  }
}

?>
