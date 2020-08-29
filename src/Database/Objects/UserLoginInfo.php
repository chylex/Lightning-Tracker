<?php
declare(strict_types = 1);

namespace Database\Objects;

use Data\UserId;
use Data\UserPassword;

final class UserLoginInfo{
  private UserId $id;
  private UserPassword $password;
  
  public function __construct(UserId $id, UserPassword $password){
    $this->id = $id;
    $this->password = $password;
  }
  
  public function getId(): UserId{
    return $this->id;
  }
  
  public function getPassword(): UserPassword{
    return $this->password;
  }
}

?>
