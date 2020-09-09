<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T003_AdminLogin_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amOnPage('/login');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
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
    $I->seeCurrentUrlEquals('/');
    $I->saveLoginToken('Admin');
  }
}

?>
