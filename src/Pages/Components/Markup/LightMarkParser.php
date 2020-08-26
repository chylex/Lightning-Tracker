<?php
declare(strict_types = 1);

namespace Pages\Components\Markup;

use Pages\Models\Tracker\IssueEditModel;

final class LightMarkParser{
  private const SPACE = 32;
  private const HASH = 35;
  private const LEFT_SQUARE_BRACKET = 91;
  private const RIGHT_SQUARE_BRACKET = 93;
  
  // TODO implement: paragraphs, block formatting, inline formatting
  
  private string $output = '';
  
  private ?string $checkbox_name;
  private int $checkbox_count = 0;
  
  private bool $last_line_empty = false;
  private bool $is_paragraph_open = false;
  
  public function __construct(?string $checkbox_name = null){
    $this->checkbox_name = $checkbox_name;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function parseLine(UnicodeIterator $iter): void{
    if (!$iter->valid()){
      $this->last_line_empty = true;
      return;
    }
    
    $heading = $this->parseHeading($iter);
    
    if ($heading !== null){
      $this->closeParagraph();
      $this->output .= $heading;
      return;
    }
    
    $iter->reset();
    $checkbox = $this->parseCheckBox($iter);
    
    if ($checkbox !== null){
      $this->closeParagraph();
      $this->output .= $checkbox;
      return;
    }
    
    $iter->reset();
    $rest = trim($this->restToString($iter));
    
    if (empty($rest)){
      $this->last_line_empty = true;
      return;
    }
    
    if ($this->last_line_empty){
      $this->closeParagraph();
    }
    
    if (!$this->is_paragraph_open){
      $this->output .= '<p>';
    }
    else{
      $this->output .= ' ';
    }
    
    $this->output .= $rest;
    $this->is_paragraph_open = true;
  }
  
  public function closeParser(): LightMarkParseResult{
    $this->closeParagraph();
    return new LightMarkParseResult($this->output, $this->checkbox_count);
  }
  
  private function closeParagraph(): void{
    if ($this->is_paragraph_open){
      $this->is_paragraph_open = false;
      $this->output .= '</p>';
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
  
  private function parseCheckBox(UnicodeIterator $iter): ?string{
    if ($iter->move() !== self::LEFT_SQUARE_BRACKET){
      return null;
    }
    
    $next = $iter->move();
    $checked = false;
    
    if (in_array($next, array_map(fn($checked_char): int => ord($checked_char), IssueEditModel::TASK_CHECKED_CHARS), true)){
      $checked = true;
      $next = $iter->move();
    }
    elseif ($next === self::SPACE){
      $next = $iter->move();
    }
    
    if ($next !== self::RIGHT_SQUARE_BRACKET){
      return null;
    }
    
    ++$this->checkbox_count;
    
    $rest = trim($this->restToString($iter));
    $checked_attr = $checked ? ' checked' : '';
    
    if ($checked){
      $rest = '<del>'.$rest.'</del>';
    }
    
    if ($this->checkbox_name === null){
      return <<<HTML
<div class="field-group">
  <input type="checkbox"$checked_attr disabled>
  <label class="disabled">$rest</label>
</div>
HTML;
    }
    else{
      $id = $this->checkbox_name.'-'.$this->checkbox_count;
      
      return <<<HTML
<div class="field-group">
  <input id="$id" name="$this->checkbox_name[]" type="checkbox" value="$this->checkbox_count"$checked_attr>
  <label for="$id">$rest</label>
</div>
HTML;
    }
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
