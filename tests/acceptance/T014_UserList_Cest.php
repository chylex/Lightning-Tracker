<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T014_UserList_Cest{
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
  public function ensureRegistrationOrder(): void{
    $db = Acceptance::getDB();
    $db->exec('UPDATE users SET date_registered = DATE_SUB(NOW(), INTERVAL 8 SECOND) WHERE name = \'Admin\'');
    $db->exec('UPDATE users SET date_registered = DATE_SUB(NOW(), INTERVAL 7 SECOND) WHERE name = \'Moderator\'');
    $db->exec('UPDATE users SET date_registered = DATE_SUB(NOW(), INTERVAL 6 SECOND) WHERE name = \'Manager1\'');
    $db->exec('UPDATE users SET date_registered = DATE_SUB(NOW(), INTERVAL 5 SECOND) WHERE name = \'Manager2\'');
    $db->exec('UPDATE users SET date_registered = DATE_SUB(NOW(), INTERVAL 4 SECOND) WHERE name = \'User1\'');
    $db->exec('UPDATE users SET date_registered = DATE_SUB(NOW(), INTERVAL 3 SECOND) WHERE name = \'User2\'');
    $db->exec('UPDATE users SET date_registered = DATE_SUB(NOW(), INTERVAL 2 SECOND) WHERE name = \'RoleLess\'');
    $db->exec('UPDATE users SET date_registered = DATE_SUB(NOW(), INTERVAL 1 SECOND) WHERE name = \'Test\'');
  }
  
  /**
   * @depends ensureRegistrationOrder
   */
  public function testUsersOrderedByRegistrationDateAscIsDefault(AcceptanceTester $I): void{
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'Manager1',
                          'Manager2',
                          'User1',
                          'User2',
                          'RoleLess',
                          'Test']);
  }
  
  /**
   * @depends ensureRegistrationOrder
   */
  public function testUsersOrderedByName(AcceptanceTester $I): void{
    $order = [
        'Admin',
        'Manager1',
        'Manager2',
        'Moderator',
        'RoleLess',
        'Test',
        'User1',
        'User2',
    ];
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
  
  /**
   * @depends ensureRegistrationOrder
   */
  public function testUsersOrderedByRole(AcceptanceTester $I): void{
    $I->click('thead tr:first-child th:nth-child(3) > a');
    
    $I->seeTableRowOrder(['Admin',     // implied admin role
                          'Moderator', // role 1
                          'Manager1',  // role 2
                          'Manager2',  // role 3
                          'User1',     // role 4, registered first
                          'User2',     // role 4, registered last
                          'RoleLess',  // no role, registered first
                          'Test']);    // no role, registered last
    
    $I->click('thead tr:first-child th:nth-child(3) > a');
    
    $I->seeTableRowOrder(['RoleLess',  // no role, registered first
                          'Test',      // no role, registered last
                          'User1',     // role 4, registered first
                          'User2',     // role 4, registered last
                          'Manager2',  // role 3
                          'Manager1',  // role 2
                          'Moderator', // role 1
                          'Admin']);   // implied admin role
  }
  
  /**
   * @depends ensureRegistrationOrder
   */
  public function testUsersOrderedByRegistrationDate(AcceptanceTester $I): void{
    $order = [
        'Admin',
        'Moderator',
        'Manager1',
        'Manager2',
        'User1',
        'User2',
        'RoleLess',
        'Test',
    ];
    
    $I->click('thead tr:first-child th:nth-child(4) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(4) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
  
  /**
   * @depends testUsersOrderedByRegistrationDateAscIsDefault
   * @depends testUsersOrderedByName
   * @depends testUsersOrderedByRole
   * @depends testUsersOrderedByRegistrationDate
   */
  public function removeTestUser(): void{
    $db = Acceptance::getDB();
    $db->exec('DELETE FROM users WHERE name = \'Test\'');
  }
}

?>
