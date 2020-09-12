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
  
  private function register(AcceptanceTester $I, string $name, string $password, string $email): void{
    $this->submit($I, $name, $password, $password, $email);
    $I->seeCurrentUrlEquals('/register?success');
    $I->saveLoginToken($name);
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
    $this->register($I, 'Moderator', '123456789', 'moderator@example.com');
  }
  
  /**
   * @depends userAlreadyExists
   * @depends emailAlreadyExists
   */
  public function registerManager1WithLogin(AcceptanceTester $I): void{
    $this->register($I, 'Manager1', '123456789', 'manager1@example.com');
  }
  
  /**
   * @depends userAlreadyExists
   * @depends emailAlreadyExists
   */
  public function registerManager2WithLogin(AcceptanceTester $I): void{
    $this->register($I, 'Manager2', '123456789', 'manager2@example.com');
  }
  
  /**
   * @depends userAlreadyExists
   * @depends emailAlreadyExists
   */
  public function registerUser1WithLogin(AcceptanceTester $I): void{
    $this->register($I, 'User1', '123456789', 'user1@example.com');
  }
  
  /**
   * @depends userAlreadyExists
   * @depends emailAlreadyExists
   */
  public function registerUser2WithLogin(AcceptanceTester $I): void{
    $this->register($I, 'User2', '987654321', 'user2@example.com');
  }
  
  /**
   * @depends registerModeratorWithLogin
   * @depends registerUser1WithLogin
   * @depends registerUser2WithLogin
   */
  public function setupRoles(): void{
    $db = Acceptance::getDB();
    
    $db->exec('INSERT INTO system_roles (id, title, ordering) VALUES (2, \'User\', 4)');
    $db->exec('INSERT INTO system_roles (id, title, ordering) VALUES (3, \'ManageUsers2\', 3)');
    $db->exec('INSERT INTO system_roles (id, title, ordering) VALUES (4, \'ManageUsers1\', 2)');
    $db->exec('INSERT INTO system_roles (id, title, ordering) VALUES (5, \'Moderator\', 1)');
    
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (2, \'projects.list\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (2, \'projects.create\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (3, \'users.list\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (3, \'users.manage\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (4, \'users.list\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (4, \'users.manage\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (5, \'projects.list\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (5, \'projects.list.all\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (5, \'projects.create\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (5, \'projects.manage\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (5, \'users.list\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (5, \'users.see.emails\')');
    $db->exec('INSERT INTO system_role_permissions (role_id, permission) VALUES (5, \'users.manage\')');
    
    $db->exec('UPDATE users SET role_id = 2 WHERE name = \'User1\' OR name = \'User2\'');
    $db->exec('UPDATE users SET role_id = 3 WHERE name = \'Manager2\'');
    $db->exec('UPDATE users SET role_id = 4 WHERE name = \'Manager1\'');
    $db->exec('UPDATE users SET role_id = 5 WHERE name = \'Moderator\'');
  }
}

?>
