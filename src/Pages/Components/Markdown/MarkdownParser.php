<?php
declare(strict_types = 1);

namespace Pages\Components\Markdown;

use function Database\protect;

final class MarkdownParser{
  private const SPACE = 32;
  private const HASH = 35;
  
  private bool $last_line_empty = false;
  private bool $is_paragraph_open = false;
  
  /** @noinspection HtmlMissingClosingTag */
  public function parseLine(UnicodeIterator $iter, string &$output): void{
    if (!$iter->valid()){
      $this->last_line_empty = true;
      return;
    }
    
    $heading = $this->parseHeading($iter);
    
    if ($heading !== null){
      $this->closeParagraph($output);
      $output .= $heading;
      return;
    }
    
    $iter->reset();
    $rest = trim($this->restToString($iter));
    
    if (empty($rest)){
      $this->last_line_empty = true;
      return;
    }
    
    if ($this->last_line_empty){
      $this->closeParagraph($output);
    }
    
    if (!$this->is_paragraph_open){
      $output .= '<p>';
    }
    else{
      $output .= ' ';
    }
    
    $output .= $rest;
    $this->is_paragraph_open = true;
  }
  
  public function closeParser(string &$output): void{
    $this->closeParagraph($output);
  }
  
  private function closeParagraph(string &$output): void{
    if ($this->is_paragraph_open){
      $this->is_paragraph_open = false;
      $output .= '</p>';
    }
    
    $this->last_line_empty = false;
  }
  
  // Elements
  
  private function parseHeading(UnicodeIterator $iter): ?string{
    if ($iter->move() !== self::HASH){
      return null;
    }
    
    $count = 1;
  
    foreach($iter as $code){
      if ($code === self::HASH){
        $count++;
      }
      elseif ($code === self::SPACE){
        break;
      }
      else{
        return null;
      }
    }
    
    if ($count > 3){
      return null;
    }
    
    $tag = 'h'.($count + 3);
    $rest = trim($this->restToString($iter));
    
    return "<$tag>$rest</$tag>";
  }
  
  private function restToString(UnicodeIterator $iter): string{
    $str = '';
    
    foreach($iter as $code){
      $str .= mb_chr($code);
    }
    
    return protect($str);
  }
}

?>
