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
  private const COOKIE_PATH = '/';
  private const COOKIE_NAME = 'logon';
  
  private const TOKEN_EXPIRATION_TIME = 365 * 24 * 60;
  
  private static ?Session $instance = null;
  
  public static function get(): Session{
    if (self::$instance === null){
      self::$instance = new Session();
    }
    
    return self::$instance;
  }
  
  private bool $logon_looked_up = false;
  private ?UserProfile $logon_user = null;
  
  private function __construct(){
  }
  
  private function checkToken(string $token, bool $renew){
    try{
      $logins = new UserLoginTable(DB::get());
      $this->logon_user = $logins->checkLogin($token);
      
      if ($renew){
        try{
          $logins->renewToken($token, self::TOKEN_EXPIRATION_TIME);
          $this->setCookie($token);
        }catch(Exception $e){
          Log::critical($e);
        }
      }
    }catch(Exception $e){
      Log::critical($e);
      $this->logon_user = null;
    }finally{
      $this->logon_looked_up = true;
    }
  }
  
  private function checkCookie(){
    if (isset($_COOKIE[self::COOKIE_NAME])){
      $this->checkToken($_COOKIE[self::COOKIE_NAME], true);
    }
    else{
      $this->logon_looked_up = true;
    }
  }
  
  public function getLogonUser(): ?UserProfile{
    if (!$this->logon_looked_up){
      $this->checkCookie();
    }
  
    return $this->logon_user;
  }
  
  public function isLoggedOn(): bool{
    return $this->getLogonUser() !== null;
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
    if ($this->logon_user !== null){
      $this->logon_looked_up = true;
      $this->logon_user = null;
    }
    
    try{
      $token = bin2hex(random_bytes(32));
      
      $logins = new UserLoginTable(DB::get());
      $logins->addOrRenewToken($id, $token, self::TOKEN_EXPIRATION_TIME);
      $this->checkToken($token, false);
      $this->setCookie($token);
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
  
    $this->logon_looked_up = false;
    $this->logon_user = null;
    $this->unsetCookie();
  }
  
  private function setCookie(string $token): void{
    $path = self::COOKIE_PATH;
    $expire_in_seconds = self::TOKEN_EXPIRATION_TIME * 60;
    header("Set-Cookie: logon=$token; Max-Age=$expire_in_seconds; Path=$path; HttpOnly; SameSite=Lax");
  }
  
  private function unsetCookie(): void{
    $path = self::COOKIE_PATH;
    header("Set-Cookie: logon=; Max-Age=0; Path=$path; HttpOnly; SameSite=Lax");
  }
}

?>
