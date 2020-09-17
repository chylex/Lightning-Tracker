<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T041_SystemSettingsRoles_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/settings/roles');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  public function seeInitialRoles(AcceptanceTester $I): void{
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User']);
  }
  
  private function createRole(AcceptanceTester $I, string $title): void{
    $I->fillField('#Create-1-Title', $title);
    $I->click('#Create-1 button[type="submit"]');
  }
  
  /**
   * @depends seeInitialRoles
   */
  public function roleAlreadyExists(AcceptanceTester $I): void{
    $this->createRole($I, 'Moderator');
    $I->seeElement('#Create-1-Title + .error');
  }
  
  /**
   * @depends roleAlreadyExists
   */
  public function roleIsCaseSensitive(AcceptanceTester $I): void{
    $this->createRole($I, 'MODERATOR');
    $I->seeElement('#Create-1-Title + .error');
  }
  
  /**
   * @depends seeInitialRoles
   */
  public function createTestRoles(AcceptanceTester $I): void{
    $this->createRole($I, 'Test1');
    $this->createRole($I, 'Test2');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test1',
                          'Test2']);
  }
  
  /**
   * @depends createTestRoles
   */
  public function moveTopRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Down"]');
    
    $I->seeTableRowOrder(['Admin',
                          'ManageUsers1',
                          'Moderator',
                          'ManageUsers2',
                          'User',
                          'Test1',
                          'Test2']);
    
    $I->click('#Move-2 button[value="Down"]');
    
    $I->seeTableRowOrder(['Admin',
                          'ManageUsers1',
                          'ManageUsers2',
                          'Moderator',
                          'User',
                          'Test1',
                          'Test2']);
    
    $I->click('#Move-3 button[value="Up"]');
    $I->click('#Move-2 button[value="Up"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test1',
                          'Test2']);
  }
  
  /**
   * @depends createTestRoles
   */
  public function moveBottomRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-6 button[value="Up"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test2',
                          'Test1']);
    
    $I->click('#Move-5 button[value="Up"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'Test2',
                          'User',
                          'Test1']);
    
    $I->click('#Move-4 button[value="Down"]');
    $I->click('#Move-5 button[value="Down"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test1',
                          'Test2']);
  }
  
  /**
   * @depends createTestRoles
   */
  public function cannotMoveOutOfBounds(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Up"]');
    $I->click('#Move-6 button[value="Down"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test1',
                          'Test2']);
  }
  
  /**
   * @depends moveTopRoleAround
   * @depends moveBottomRoleAround
   * @depends cannotMoveOutOfBounds
   */
  public function cannotDeleteAdminRole(AcceptanceTester $I): void{
    $I->fillField('#Delete-1 input[type="hidden"][name="Role"]', 1);
    $I->click('#Delete-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test1',
                          'Test2']);
  }
  
  /**
   * @depends moveTopRoleAround
   * @depends moveBottomRoleAround
   * @depends cannotMoveOutOfBounds
   */
  public function deleteRole(AcceptanceTester $I): void{
    $I->click('#Delete-5 button[type="submit"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test2']);
  }
  
  /**
   * @depends deleteRole
   */
  public function readdRole(AcceptanceTester $I): void{
    $this->createRole($I, 'Test1');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test2',
                          'Test1']);
  }
}

?>
