<?php
declare(strict_types = 1);

namespace Database\Objects;

use Data\UserId;
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
  
  private string $id;
  private string $password_hash;
  
  public function __construct(string $id, string $password_hash){
    $this->id = $id;
    $this->password_hash = $password_hash;
  }
  
  public function getId(): UserId{
    return UserId::fromRaw($this->id);
  }
  
  public function checkPassword(string $password): bool{
    return password_verify($password, $this->password_hash);
  }
}

?>
