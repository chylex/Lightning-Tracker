<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T031_UserSorting_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/users');
  }
  
  public function registerTestUser(AcceptanceTester $I): void{
    (new T030_UserCreation_Cest())->registerTestUser($I);
  }
  
  /**
   * @depends registerTestUser
   */
  public function sortByRegistrationDateAscIsDefault(AcceptanceTester $I): void{
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'Manager1',
                          'Manager2',
                          'User1',
                          'User3',
                          'User2',
                          'RoleLess',
                          'Test']);
  }
  
  /**
   * @depends registerTestUser
   */
  public function sortByName(AcceptanceTester $I): void{
    $order = [
        'Admin',
        'Manager1',
        'Manager2',
        'Moderator',
        'RoleLess',
        'Test',
        'User1',
        'User2',
        'User3',
    ];
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
  
  /**
   * @depends registerTestUser
   */
  public function sortByRole(AcceptanceTester $I): void{
    $I->click('thead tr:first-child th:nth-child(3) > a');
    
    $I->seeTableRowOrder(['Admin',     // implied admin role
                          'Moderator', // role 1
                          'Manager1',  // role 2
                          'Manager2',  // role 3
                          'User1',     // role 4, registered first
                          'User3',     // role 4, registered second
                          'User2',     // role 4, registered last
                          'RoleLess',  // no role, registered first
                          'Test']);    // no role, registered last
    
    $I->click('thead tr:first-child th:nth-child(3) > a');
    
    $I->seeTableRowOrder(['RoleLess',  // no role, registered first
                          'Test',      // no role, registered last
                          'User1',     // role 4, registered first
                          'User3',     // role 4, registered second
                          'User2',     // role 4, registered last
                          'Manager2',  // role 3
                          'Manager1',  // role 2
                          'Moderator', // role 1
                          'Admin']);   // implied admin role
  }
  
  /**
   * @depends registerTestUser
   */
  public function sortByRoleThenName(AcceptanceTester $I): void{
    $I->click('thead tr:first-child th:nth-child(3) > a');
    $I->click('thead tr:first-child th:nth-child(1) > a');
    
    $I->seeTableRowOrder(['Admin',     // implied admin role
                          'Moderator', // role 1
                          'Manager1',  // role 2
                          'Manager2',  // role 3
                          'User1',     // role 4, alphabetically first
                          'User2',     // role 4, alphabetically second
                          'User3',     // role 4, alphabetically last
                          'RoleLess',  // no role, alphabetically first
                          'Test']);    // no role, alphabetically last
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    
    $I->seeTableRowOrder(['Admin',      // implied admin role
                          'Moderator',  // role 1
                          'Manager1',   // role 2
                          'Manager2',   // role 3
                          'User3',      // role 4, alphabetically last
                          'User2',      // role 4, alphabetically second
                          'User1',      // role 4, alphabetically first
                          'Test',       // no role, alphabetically last
                          'RoleLess']); // no role, alphabetically first
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->click('thead tr:first-child th:nth-child(3) > a');
    $I->click('thead tr:first-child th:nth-child(1) > a');
    
    $I->seeTableRowOrder(['RoleLess',  // no role, alphabetically first
                          'Test',      // no role, alphabetically last
                          'User1',     // role 4, alphabetically first
                          'User2',     // role 4, alphabetically second
                          'User3',     // role 4, alphabetically last
                          'Manager2',  // role 3
                          'Manager1',  // role 2
                          'Moderator', // role 1
                          'Admin']);   // implied admin role
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    
    $I->seeTableRowOrder(['Test',      // no role, alphabetically last
                          'RoleLess',  // no role, alphabetically first
                          'User3',     // role 4, alphabetically last
                          'User2',     // role 4, alphabetically second
                          'User1',     // role 4, alphabetically first
                          'Manager2',  // role 3
                          'Manager1',  // role 2
                          'Moderator', // role 1
                          'Admin']);   // implied admin role
  }
  
  /**
   * @depends registerTestUser
   */
  public function sortByRoleThenRegistrationDate(AcceptanceTester $I): void{
    $I->click('thead tr:first-child th:nth-child(3) > a');
    
    $I->seeTableRowOrder(['Admin',     // implied admin role
                          'Moderator', // role 1
                          'Manager1',  // role 2
                          'Manager2',  // role 3
                          'User1',     // role 4, registered first
                          'User3',     // role 4, registered second
                          'User2',     // role 4, registered last
                          'RoleLess',  // no role, registered first
                          'Test']);    // no role, registered last
    
    $I->click('thead tr:first-child th:nth-child(3) > a');
    
    $I->seeTableRowOrder(['RoleLess',  // no role, registered first
                          'Test',      // no role, registered last
                          'User1',     // role 4, registered first
                          'User3',     // role 4, registered second
                          'User2',     // role 4, registered last
                          'Manager2',  // role 3
                          'Manager1',  // role 2
                          'Moderator', // role 1
                          'Admin']);   // implied admin role
  }
  
  /**
   * @depends registerTestUser
   */
  public function sortByRegistrationDate(AcceptanceTester $I): void{
    $order = [
        'Admin',
        'Moderator',
        'Manager1',
        'Manager2',
        'User1',
        'User3',
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
   * @depends sortByRegistrationDateAscIsDefault
   * @depends sortByName
   * @depends sortByRole
   * @depends sortByRoleThenName
   * @depends sortByRoleThenRegistrationDate
   * @depends sortByRegistrationDate
   */
  public function removeTestUser(): void{
    (new T030_UserCreation_Cest())->removeTestUser();
  }
}

?>
