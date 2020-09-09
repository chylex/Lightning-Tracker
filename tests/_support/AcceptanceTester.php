<?php
declare(strict_types = 1);

use Codeception\Actor;
use Codeception\Test\Test;
use PHPUnit\Framework\TestResult;


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
  
  public function terminate(): void{
    $scenario = (array)$this->getScenario();
    
    /** @var Test $test */
    $test = $scenario["\x00*\x00test"];
    
    /** @var TestResult $result */
    $result = $test->getTestResultObject();
    $result->stop();
  }
  
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
  
  public function amNotLoggedIn(bool $reload = false): void{
    $this->resetCookie('logon');
  
    if ($reload && isset($this->page)){
      $this->amOnPage($this->page);
    }
  }
  
  public function amLoggedIn(string $user, bool $reload = false): void{
    $this->setCookie('logon', self::$tokens[$user], [
        'path'     => '/',
        'domain'   => 'localhost',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    if ($reload && isset($this->page)){
      $this->amOnPage($this->page);
    }
  }
  
  public function amOnPage(string $page): void{
    $this->amOnPageInternal($page);
    $this->page = $page;
  }
}
