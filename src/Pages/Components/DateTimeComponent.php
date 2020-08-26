<?php
declare(strict_types = 1);

namespace Pages\Components;

use Pages\IViewable;

final class DateTimeComponent implements IViewable{
  public static function echoHead(): void{
    echo '<script type="text/javascript" src="~resources/js/datetime.js?v='.TRACKER_RESOURCE_VERSION.'"></script>';
  }
  
  private int $datetime;
  private bool $date_only;
  
  public function __construct(string $datetime, bool $date_only = false){
    $this->datetime = strtotime($datetime);
    $this->date_only = $date_only;
  }
  
  public function echoBody(): void{
    $standard = date(DATE_RFC3339, $this->datetime);
    $readable = date($this->date_only ? 'd M Y, e' : 'd M Y, H:i e', $this->datetime);
    
    echo '<time datetime="'.$standard.'" data-kind="'.($this->date_only ? 'date' : 'datetime').'">'.$readable.'</time>';
  }
}

?>
