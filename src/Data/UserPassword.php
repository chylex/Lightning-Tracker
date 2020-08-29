<?php
declare(strict_types = 1);

namespace Data;

use Exception;

final class UserPassword{
  /**
   * @param string $password
   * @return UserPassword
   * @throws Exception
   */
  public static function hash(string $password): self{
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    if (!$hash){
      throw new Exception('Hashing function failed.');
    }
    
    return new self($hash);
  }
  
  private string $password_hash;
  
  public function __construct(string $password_hash){
    $this->password_hash = $password_hash;
  }
  
  public function check(string $password): bool{
    return password_verify($password, $this->password_hash);
  }
  
  public function __toString(): string{
    return $this->password_hash;
  }
}

?>
