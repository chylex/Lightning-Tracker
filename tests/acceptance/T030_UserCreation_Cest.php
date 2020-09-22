<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T030_UserCreation_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/users');
  }
  
  private function createUser(AcceptanceTester $I, string $name, string $password, string $email): void{
    $I->fillField('#Create-1-Name', $name);
    $I->fillField('#Create-1-Password', $password);
    $I->fillField('#Create-1-Email', $email);
    $I->click('#Create-1 button[type="submit"]');
  }
  
  public function userAndEmailAlreadyExists(AcceptanceTester $I): void{
    $this->createUser($I, 'Admin', '123456789', 'moderator@example.com');
    $I->seeElement('#Create-1-Name + .error');
    $I->seeElement('#Create-1-Email + .error');
  }
  
  public function passwordNotLongEnough(AcceptanceTester $I): void{
    $this->createUser($I, 'Test', '123456', 'test@example.com');
    $I->seeElement('input[name="Password"] + .error');
  }
  
  public function registerTestUser(AcceptanceTester $I): void{
    $this->createUser($I, 'Test', '123456789', 'test@example.com');
    
    $I->seeInDatabase('users', [
        'name'    => 'Test',
        'email'   => 'test@example.com',
        'role_id' => null,
    ]);
    
    $I->amNotLoggedIn();
    $I->amOnPage('/login');
    $I->fillField('Name', 'Test');
    $I->fillField('Password', '123456789');
    $I->click('button[type="submit"]');
    $I->seeCurrentUrlEquals('/');
  }
  
  /**
   * @depends registerTestUser
   */
  public function removeTestUser(): void{
    Acceptance::getDB()->exec('DELETE FROM users WHERE name = \'Test\'');
  }
}

?>
