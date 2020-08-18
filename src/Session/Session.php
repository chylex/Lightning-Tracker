<?php
declare(strict_types = 1);

namespace Session;

use Database\DB;
use Database\Objects\UserProfile;
use Database\Tables\UserLoginTable;
use Database\Tables\UserTable;
use Exception;
use Logging\Log;

final class Session{
  private const COOKIE_NAME = 'logon';
  private const TOKEN_EXPIRATION_TIME = 365 * 24 * 60;
  
  private static ?Session $instance = null;
  
  public static function get(): self{
    if (self::$instance === null){
      self::$instance = new self();
    }
    
    return self::$instance;
  }
  
  private static function checkToken(string $token, bool $renew): SessionLoginInfo{
    try{
      $logins = new UserLoginTable(DB::get());
      $login = $logins->checkLogin($token);
      $login = $login === null ? SessionLoginInfo::guest() : SessionLoginInfo::user($login);
      
      if ($renew){
        try{
          $logins->renewToken($token, self::TOKEN_EXPIRATION_TIME);
          self::setCookie($token);
        }catch(Exception $e){
          Log::critical($e);
        }
      }
      
      return $login;
    }catch(Exception $e){
      Log::critical($e);
      return SessionLoginInfo::guest();
    }
  }
  
  private static function checkCookie(): SessionLoginInfo{
    if (isset($_COOKIE[self::COOKIE_NAME])){
      return self::checkToken($_COOKIE[self::COOKIE_NAME], true);
    }
    else{
      return SessionLoginInfo::guest();
    }
  }
  
  private static function setCookie(string $token): void{
    $path = BASE_PATH_ENC;
    $expire_in_seconds = self::TOKEN_EXPIRATION_TIME * 60;
    header("Set-Cookie: logon=$token; Max-Age=$expire_in_seconds; Path=$path/; HttpOnly; SameSite=Lax");
  }
  
  private static function unsetCookie(): void{
    $path = BASE_PATH_ENC;
    header("Set-Cookie: logon=; Max-Age=0; Path=$path/; HttpOnly; SameSite=Lax");
  }
  
  private ?SessionLoginInfo $login = null;
  
  private function __construct(){
  }
  
  private function getLogin(): SessionLoginInfo{
    if ($this->login === null){
      $this->login = self::checkCookie();
    }
    
    return $this->login;
  }
  
  public function getLogonUser(): ?UserProfile{
    return $this->getLogin()->getLogonUser();
  }
  
  public function isLoggedOn(): bool{
    return $this->getLogonUser() !== null;
  }
  
  public function getPermissions(): Permissions{
    return $this->getLogin()->getPermissions();
  }
  
  public function tryLoginWithName(string $name): bool{
    $id = null;
    
    try{
      $users = new UserTable(DB::get());
      $id = $users->findIdByName($name);
    }catch(Exception $e){
      Log::critical($e);
      return false;
    }
    
    return $id !== null && $this->tryLoginWithId($id);
  }
  
  public function tryLoginWithId(int $id): bool{
    if ($this->login !== null){
      $this->login = SessionLoginInfo::guest();
    }
    
    try{
      $token = bin2hex(random_bytes(32));
      
      $logins = new UserLoginTable(DB::get());
      $logins->addOrRenewToken($id, $token, self::TOKEN_EXPIRATION_TIME);
      
      $this->login = self::checkToken($token, false);
      self::setCookie($token);
      return true;
    }catch(Exception $e){
      Log::critical($e);
      return false;
    }
  }
  
  public function destroyCurrentLogin(): void{
    if (isset($_COOKIE[self::COOKIE_NAME])){
      try{
        $logins = new UserLoginTable(DB::get());
        $logins->destroyToken($_COOKIE[self::COOKIE_NAME]);
      }catch(Exception $e){
        Log::critical($e);
      }finally{
        unset($_COOKIE[self::COOKIE_NAME]);
      }
    }
    
    $this->login = null;
    self::unsetCookie();
  }
}

?>
