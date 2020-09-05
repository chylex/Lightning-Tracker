<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T004_AdminLogout_Cest{
  private function tryUseToken(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/account');
  }
  
  public function logout(AcceptanceTester $I): void{
    $this->tryUseToken($I);
    $I->submitForm('#Logout', []);
    
    $I->seeCookie('logon', [
        'value'   => '',
        'expires' => null
    ]);
    
    $I->seeCurrentUrlEquals('/');
  }
  
  /**
   * @depends logout
   */
  public function cannotReuseToken(AcceptanceTester $I): void{
    $this->tryUseToken($I);
    $I->seeCurrentUrlEquals('/login?return=account');
  }
  
  /**
   * @depends cannotReuseToken
   */
  public function loginAgain(AcceptanceTester $I): void{
    $login = new T003_AdminLogin_Cest();
    $login->_before($I);
    $login->login($I);
    $I->saveLoginToken('Admin');
  }
}

?>
