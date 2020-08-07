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
    $parser = new MarkdownParser();
    $iter = new UnicodeIterator();
    
    $lines = mb_split("\n", $this->text);
    $output = '';
    
    foreach($lines as $line){
      $iter->prepare($line);
      $parser->parseLine($iter, $output);
    }
    
    $parser->closeParser($output);
    echo $output;
  }
}

?>
