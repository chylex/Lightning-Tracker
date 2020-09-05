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
  use _generated\AcceptanceTesterActions;
  
  private static array $tokens = [];
  
  public function saveLoginToken(string $user): void{
    self::$tokens[$user] = $this->grabCookie('logon');
  }
  
  public function amLoggedIn(string $user): void{
    $this->setCookie('logon', self::$tokens[$user], [
        'path'     => '/',
        'domain'   => 'localhost',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
  }
}
