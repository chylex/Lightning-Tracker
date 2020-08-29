<?php
declare(strict_types = 1);

namespace Data;

use Exception;
use Logging\Log;

final class UserId{
  private const ID_CHARS = '2456789bcdfghjklmnpqrstvwxyz';
  private const ID_LENGTH = 9;
  
  public static function fromRaw(string $id): self{
    return new self($id);
  }
  
  public static function fromFormatted(string $formatted): self{
    return new self(str_replace('-', '', $formatted));
  }
  
  public static function generateNew(): UserId{
    $str = '';
    
    for($i = 0, $max = strlen(self::ID_CHARS) - 1; $i < self::ID_LENGTH; $i++){
      $str .= self::ID_CHARS[self::rand($max)];
    }
    
    return new UserId($str);
  }
  
  /** @noinspection RandomApiMigrationInspection */
  private static function rand(int $max): int{
    try{
      $pick = random_int(0, $max);
    }catch(Exception $e){
      Log::critical($e);
      $pick = mt_rand(0, $max); // cryptographical security is preferable but not required
    }
    
    return $pick;
  }
  
  private string $id;
  
  private function __construct(string $id){
    $this->id = $id;
  }
  public function equals(?UserId $other): bool{
    return $other !== null && $this->id === $other->id;
  }
  
  public function raw(): string{
    return $this->id;
  }
  
  public function formatted(): string{
    return implode('-', str_split($this->id, 3));
  }
}

?>
