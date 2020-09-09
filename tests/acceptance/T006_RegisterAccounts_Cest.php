<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T006_RegisterAccounts_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amOnPage('/register');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function submit(AcceptanceTester $I, string $name, string $password, string $password_repeated, string $email): void{
    $I->fillField('Name', $name);
    $I->fillField('Password', $password);
    $I->fillField('PasswordRepeated', $password_repeated);
    $I->fillField('Email', $email);
    $I->click('button[type="submit"]');
  }
  
  public function userAlreadyExists(AcceptanceTester $I): void{
    $this->submit($I, 'Admin', '123456789', '123456789', 'user@example.com');
    $I->seeElement('input[name="Name"] + .error');
  }
  
  public function emailAlreadyExists(AcceptanceTester $I): void{
    $this->submit($I, 'User', '123456789', '123456789', 'admin@example.com');
    $I->seeElement('input[name="Email"] + .error');
  }
  
  public function userAndEmailAreCaseInsensitive(AcceptanceTester $I): void{
    $this->submit($I, 'ADMIN', '123456789', '123456789', 'Admin@Example.Com');
    $I->seeElement('input[name="Name"] + .error');
    $I->seeElement('input[name="Email"] + .error');
  }
  
  public function passwordNotLongEnough(AcceptanceTester $I): void{
    $this->submit($I, 'User', '123456', '123456', 'user@example.com');
    $I->seeElement('input[name="Password"] + .error');
  }
  
  public function passwordDoesNotMatch(AcceptanceTester $I): void{
    $this->submit($I, 'User', '123456789', '123456780', 'user@example.com');
    $I->seeElement('input[name="PasswordRepeated"] + .error');
  }
  
  /**
   * @depends userAlreadyExists
   * @depends emailAlreadyExists
   */
  public function registerModeratorWithLogin(AcceptanceTester $I): void{
    $this->submit($I, 'Moderator', '123456789', '123456789', 'moderator@example.com');
    $I->seeCurrentUrlEquals('/register?success');
    $I->saveLoginToken('Moderator');
  }
  
  /**
   * @depends userAlreadyExists
   * @depends emailAlreadyExists
   */
  public function registerUser1WithLogin(AcceptanceTester $I): void{
    $this->submit($I, 'User1', '123456789', '123456789', 'user1@example.com');
    $I->seeCurrentUrlEquals('/register?success');
    $I->saveLoginToken('User1');
  }
  
  /**
   * @depends userAlreadyExists
   * @depends emailAlreadyExists
   */
  public function registerUser2WithLogin(AcceptanceTester $I): void{
    $this->submit($I, 'User2', '987654321', '987654321', 'user2@example.com');
    $I->seeCurrentUrlEquals('/register?success');
    $I->saveLoginToken('User2');
  }
  
  /**
   * @depends registerModeratorWithLogin
   * @depends registerUser1WithLogin
   * @depends registerUser2WithLogin
   */
  public function setupRoles(): void{
    $db = Acceptance::getDB();
    
    $db->exec('INSERT INTO system_roles (id, title, ordering, special) VALUES (1, \'User\', 2, FALSE)');
    $db->exec('INSERT INTO system_roles (id, title, ordering, special) VALUES (2, \'Moderator\', 1, FALSE)');
    
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (1, \'projects.list\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (1, \'projects.create\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (2, \'projects.list\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (2, \'projects.list.all\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (2, \'projects.create\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (2, \'projects.manage\')');
    
    $db->exec('UPDATE users SET role_id = 1 WHERE name = \'User1\' OR name = \'User2\'');
    $db->exec('UPDATE users SET role_id = 2 WHERE name = \'Moderator\'');
  }
}

?>
