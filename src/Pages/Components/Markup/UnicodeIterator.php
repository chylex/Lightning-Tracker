<?php
declare(strict_types = 1);

namespace Pages\Components\Markup;

use Iterator;

final class UnicodeIterator implements Iterator{
  private const LEN1 = 0b0_1000_0000;
  private const LEN2 = 0b0_1110_0000;
  private const AND2 = 0b0_0001_1111;
  private const LEN3 = 0b0_1111_0000;
  private const AND3 = 0b0_0000_1111;
  private const LEN4 = 0b0_1111_1000;
  private const AND4 = 0b0_0000_1111;
  private const CONT = 0b0_0011_1111;
  
  private string $text;
  private int $pos;
  private int $last_size;
  
  public function prepare(string &$text): void{
    $this->text = &$text;
    $this->reset();
  }
  
  public function reset(): void{
    $this->pos = 0;
    $this->last_size = 0;
  }
  
  public function rewind(): void{
    // allow multiple foreach loops in parsing
  }
  
  public function valid(): bool{
    return isset($this->text[$this->pos]);
  }
  
  public function key(){
    return $this->pos;
  }
  
  public function current(): int{
    $ord = ord($this->text[$this->pos]);
    
    if ($ord < self::LEN1){
      $this->last_size = 1;
      $chr = $ord;
    }
    elseif ($ord < self::LEN2){
      $this->last_size = 2;
      
      $b2 = $this->text[$this->pos + 1] ?? '\0';
      
      $chr = (($ord & self::AND2) << 6) | (ord($b2) & self::CONT);
    }
    elseif ($ord < self::LEN3){
      $this->last_size = 3;
      
      $b2 = $this->text[$this->pos + 1] ?? '\0';
      $b3 = $this->text[$this->pos + 2] ?? '\0';
      
      $chr = (($ord & self::AND3) << 12) | ((ord($b2) & self::CONT) << 6) | (ord($b3) & self::CONT);
    }
    elseif ($ord < self::LEN4){
      $this->last_size = 4;
      
      $b2 = $this->text[$this->pos + 1] ?? '\0';
      $b3 = $this->text[$this->pos + 2] ?? '\0';
      $b4 = $this->text[$this->pos + 3] ?? '\0';
      
      $chr = (($ord & self::AND4) << 18) | ((ord($b2) & self::CONT) << 12) | ((ord($b3) & self::CONT) << 6) | (ord($b4) & self::CONT);
    }
    else{
      $this->last_size = 1;
      $chr = 0;
    }
    
    return $chr;
  }
  
  public function next(): void{
    $this->pos += $this->last_size;
    $this->last_size = 0;
  }
  
  public function move(): int{
    if (!$this->valid()){
      return 0;
    }
    
    $chr = $this->current();
    $this->next();
    return $chr;
  }
}

?>
