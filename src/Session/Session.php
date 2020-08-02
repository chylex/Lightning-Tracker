<?php
declare(strict_types = 1);

namespace Session;

final class Session{
  private static ?Session $instance = null;
  
  public static function get(): Session{
    if (self::$instance === null){
      self::$instance = new Session();
    }
    
    return self::$instance;
  }
  
  private function __construct(){
  }
}

?>
