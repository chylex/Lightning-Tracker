<?php
declare(strict_types = 1);

namespace Database\Objects;

use Exception;

final class UserLoginInfo{
  /**
   * @param string $password
   * @return string
   * @throws Exception
   */
  public static function hashPassword(string $password): string{
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    if (!$hash){
      throw new Exception('Fatal error, hashing function failed.');
    }
    
    return $hash;
  }
  
  private int $id;
  private string $password_hash;
  
  public function __construct(int $id, string $password_hash){
    $this->id = $id;
    $this->password_hash = $password_hash;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function checkPassword(string $password): bool{
    return password_verify($password, $this->password_hash);
  }
}

?>
