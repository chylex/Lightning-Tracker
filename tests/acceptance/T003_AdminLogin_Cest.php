<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T003_AdminLogin_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amOnPage('/login');
  }
  
  public function invalidUsername(AcceptanceTester $I): void{
    $I->fillField('Name', 'InvalidUser');
    $I->fillField('Password', '123456789');
    $I->click('button[type="submit"]');
    $I->see('Invalid username or password.', '.error');
  }
  
  public function invalidPassword(AcceptanceTester $I): void{
    $I->fillField('Name', 'Admin');
    $I->fillField('Password', 'InvalidPassword');
    $I->click('button[type="submit"]');
    $I->see('Invalid username or password.', '.error');
  }
  
  public function login(AcceptanceTester $I): void{
    $I->fillField('Name', 'Admin');
    $I->fillField('Password', '123456789');
    $I->click('button[type="submit"]');
    
    $I->seeCookie('logon', [
        'path'     => '/',
        'domain'   => 'localhost',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    $I->assertNotEmpty($I->grabCookie('logon'));
    $I->seeCurrentUrlEquals('/');
    $I->saveLoginToken('Admin');
  }
}

?>
