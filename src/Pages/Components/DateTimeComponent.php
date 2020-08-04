<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class DateTimeComponent implements IViewable{
  private int $datetime;
  
  public function __construct(string $datetime){
    $this->datetime = strtotime($datetime);
  }
  
  public function echoBody(): void{
    echo '<time datetime="'.date(DATE_RFC3339, $this->datetime).'">'.date('d M Y, H:i e', $this->datetime).'</time>';
  }
}

?>
