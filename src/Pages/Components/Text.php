<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class Text implements IViewable{
  public static function plain(string $text): self{
    return new self(protect($text));
  }
  
  public static function missing(string $text): self{
    return new self('<span class="missing">'.protect($text).'</span>');
  }
  
  public static function withIcon(string $text, string $icon): self{
    return new self('<span class="icon icon-'.$icon.'"></span> '.protect($text));
  }
  
  public static function checkmark(string $text): self{
    return self::withIcon($text, 'checkmark');
  }
  
  public static function warning(string $text): self{
    return self::withIcon($text, 'warning');
  }
  
  private string $html;
  
  private function __construct(string $html){
    $this->html = $html;
  }
  
  public function getHtml(): string{
    return $this->html;
  }
  
  public function echoBody(): void{
    echo $this->html;
  }
}

?>
