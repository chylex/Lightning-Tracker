<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T152_ProjectSettingsRoleCreation_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/settings/roles');
  }
  
  public function seeInitialRoles(AcceptanceTester $I): void{
    $I->seeTableRowOrder(['Owner',
                          'Administrator',
                          'Moderator',
                          'Developer',
                          'Reporter']);
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
    
    $I->seeTableRowOrder(['Owner',
                          'Administrator',
                          'Moderator',
                          'Developer',
                          'Reporter',
                          'Test1',
                          'Test2']);
  }
  
  /**
   * @depends seeInitialRoles
   */
  public function cannotDeleteOwnerRole(AcceptanceTester $I): void{
    $I->fillField('#Delete-1 input[type="hidden"][name="Role"]', 1);
    $I->click('#Delete-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Owner',
                          'Administrator',
                          'Moderator',
                          'Developer',
                          'Reporter',
                          'Test1',
                          'Test2']);
  }
  
  /**
   * @depends createTestRoles
   */
  public function deleteRole(AcceptanceTester $I): void{
    $I->click('#Delete-5 button[type="submit"]');
    
    $I->seeTableRowOrder(['Owner',
                          'Administrator',
                          'Moderator',
                          'Developer',
                          'Reporter',
                          'Test2']);
  }
  
  /**
   * @depends deleteRole
   */
  public function readdRole(AcceptanceTester $I): void{
    $this->createRole($I, 'Test1');
    
    $I->seeTableRowOrder(['Owner',
                          'Administrator',
                          'Moderator',
                          'Developer',
                          'Reporter',
                          'Test2',
                          'Test1']);
  }
}

?>
