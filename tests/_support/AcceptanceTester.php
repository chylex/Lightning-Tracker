<?php
declare(strict_types = 1);

use Codeception\Actor;


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor{
  use _generated\AcceptanceTesterActions {
    amOnPage as private amOnPageInternal;
  }
  
  private static array $tokens = [];
  private string $page;
  
  public function saveLoginToken(string $user): void{
    $this->seeCookie('logon', [
        'path'     => '/',
        'domain'   => 'localhost',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    $token = $this->grabCookie('logon');
    
    $this->assertNotEmpty($token);
    self::$tokens[$user] = $token;
  }
  
  public function amNotLoggedIn(): void{
    $this->resetCookie('logon');
  }
  
  public function amLoggedIn(string $user): void{
    $this->setCookie('logon', self::$tokens[$user], [
        'path'     => '/',
        'domain'   => 'localhost',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    if (isset($this->page)){
      $this->amOnPage($this->page);
    }
  }
  
  public function amOnPage(string $page): void{
    $this->amOnPageInternal($page);
    $this->page = $page;
  }
}
