<?php
declare(strict_types = 1);

namespace Pages\Components\Markup;

use Pages\Models\Project\IssueEditModel;

final class LightMarkParser{
  private const SPACE = 32;
  private const EXCLAMATION_MARK = 33;
  private const HASH = 35;
  private const LEFT_PARENTHESIS = 40;
  private const RIGHT_PARENTHESIS = 41;
  private const LEFT_SQUARE_BRACKET = 91;
  private const RIGHT_SQUARE_BRACKET = 93;
  
  // TODO implement: block formatting, inline formatting
  
  private LightMarkProperties $props;
  private string $output = '';
  
  private int $checkbox_count = 0;
  
  private bool $last_line_empty = false;
  private bool $is_paragraph_open = false;
  
  public function __construct(LightMarkProperties $props){
    $this->props = $props;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function parseLine(UnicodeIterator $iter): void{
    if (!$iter->valid()){
      $this->last_line_empty = true;
      return;
    }
    
    if ($this->handleFullLineElement($iter, $this->parseHeading($iter)) ||
        $this->handleFullLineElement($iter, $this->parseCheckBox($iter))
    ){
      return;
    }
    
    $rest = trim($this->parseRestAsInline($iter));
    
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
      $this->output .= '<br>';
    }
    
    $this->output .= $rest;
    $this->is_paragraph_open = true;
  }
  
  public function closeParser(): LightMarkParseResult{
    $this->closeParagraph();
    return new LightMarkParseResult('<div class="lightmark">'.$this->output.'</div>', $this->checkbox_count);
  }
  
  private function closeParagraph(): void{
    if ($this->is_paragraph_open){
      $this->is_paragraph_open = false;
      $this->output .= '</p>';
    }
    
    $this->last_line_empty = false;
  }
  
  // Helpers
  
  private function readUntil(UnicodeIterator $iter, int $terminator): ?string{
    $text = '';
    
    while($iter->valid()){
      $next = $iter->move();
      
      if ($next === $terminator){
        return $text;
      }
      else{
        $text .= mb_chr($next);
      }
    }
    
    return null;
  }
  
  // Full line elements
  
  private function handleFullLineElement(UnicodeIterator $iter, ?string $parsed_element): bool{
    if ($parsed_element === null){
      $iter->reset();
      return false;
    }
    else{
      $this->closeParagraph();
      $this->output .= $parsed_element;
      return true;
    }
  }
  
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
    $rest = trim($this->parseRestAsInline($iter));
    
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
    
    $rest = trim($this->parseRestAsInline($iter));
    $checked_attr = $checked ? ' checked' : '';
    
    if ($checked){
      $rest = '<del>'.$rest.'</del>';
    }
    
    $cb_name = $this->props->getCheckBoxName();
    
    if ($cb_name === null){
      return <<<HTML
<div class="field-group">
  <input type="checkbox"$checked_attr disabled>
  <label class="disabled">$rest</label>
</div>
HTML;
    }
    else{
      $id = $cb_name.'-'.$this->checkbox_count;
      $name = $cb_name.'[]';
      
      return <<<HTML
<div class="field-group">
  <input id="$id" name="$name" type="checkbox" value="$this->checkbox_count"$checked_attr>
  <label for="$id">$rest</label>
</div>
HTML;
    }
  }
  
  // Inline elements
  
  private function parseRestAsInline(UnicodeIterator $iter): string{
    $str = '';
    
    while($iter->valid()){
      $next = $iter->move();
      $parsed = null;
      
      if ($next === self::LEFT_SQUARE_BRACKET){
        $iter->beginSection();
        $parsed = $this->thenParseLink($iter);
      }
      elseif ($next === self::EXCLAMATION_MARK){
        $iter->beginSection();
        $parsed = $this->thenParseImage($iter);
      }
      else{
        $str .= protect(mb_chr($next));
        continue;
      }
      
      if ($parsed === null){
        $iter->rewindSection();
        $str .= protect(mb_chr($next));
      }
      else{
        $iter->endSection();
        $str .= $parsed;
      }
    }
    
    return $str;
  }
  
  private function thenParseLink(UnicodeIterator $iter): ?string{
    $text = $this->readUntil($iter, self::RIGHT_SQUARE_BRACKET);
    
    if ($text === null || $iter->move() !== self::LEFT_PARENTHESIS){
      return null;
    }
    
    $url = $this->readUntil($iter, self::RIGHT_PARENTHESIS);
    
    if ($url === null){
      return null;
    }
    
    $scheme = parse_url($url, PHP_URL_SCHEME);
    
    if ($scheme === null || !$this->props->isAllowedLinkScheme($scheme)){
      return null;
    }
    
    $url = protect($url);
    $text = protect($text);
    
    return '<a href="'.$url.'">'.$text.'</a>';
  }
  
  private function thenParseImage(UnicodeIterator $iter): ?string{
    if ($iter->move() !== self::LEFT_SQUARE_BRACKET){
      return null;
    }
    
    $alt = $this->readUntil($iter, self::RIGHT_SQUARE_BRACKET);
    
    if ($alt === null || $iter->move() !== self::LEFT_PARENTHESIS){
      return null;
    }
    
    $url = $this->readUntil($iter, self::RIGHT_PARENTHESIS);
    
    if ($url === null){
      return null;
    }
    
    $scheme = parse_url($url, PHP_URL_SCHEME);
    
    if ($scheme === null || !$this->props->isAllowedImageScheme($scheme)){
      return null;
    }
    
    $url = protect($url);
    $alt = protect($alt);
    
    return '<a href="'.$url.'" title="'.$alt.'"><img src="'.$url.'" alt="'.$alt.'"></a>';
  }
}

?>
