<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T011_SystemSettingsRoles_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/settings/roles');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  public function seeInitialRoles(AcceptanceTester $I): void{
    $I->see('Moderator', 'tbody tr:nth-child(1)');
    $I->see('User', 'tbody tr:nth-child(2)');
  }
  
  private function createRole(AcceptanceTester $I, string $title): void{
    $I->fillField('#Create-1-Title', $title);
    $I->click('#Create-1 button[type="submit"]');
  }
  
  private function verifyRoleOrder(AcceptanceTester $I, array $roles): void{
    foreach($roles as $i => $role){
      $I->see($role, 'tbody tr:nth-child('.($i + 1).')');
    }
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
  public function createAdditionalRoles(AcceptanceTester $I): void{
    $this->createRole($I, 'ManageUsers1');
    $this->createRole($I, 'ManageUsers2');
    
    $this->verifyRoleOrder($I, [
        'Moderator',
        'User',
        'ManageUsers1',
        'ManageUsers2'
    ]);
  }
  
  /**
   * @depends createAdditionalRoles
   */
  public function moveTopRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Down"]');
    
    $this->verifyRoleOrder($I, [
        'User',
        'Moderator',
        'ManageUsers1',
        'ManageUsers2'
    ]);
    
    $I->click('#Move-2 button[value="Up"]');
    
    $this->verifyRoleOrder($I, [
        'Moderator',
        'User',
        'ManageUsers1',
        'ManageUsers2'
    ]);
  }
  
  /**
   * @depends createAdditionalRoles
   */
  public function moveBottomRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-4 button[value="Up"]');
    
    $this->verifyRoleOrder($I, [
        'Moderator',
        'User',
        'ManageUsers2',
        'ManageUsers1'
    ]);
    
    $I->click('#Move-3 button[value="Down"]');
    
    $this->verifyRoleOrder($I, [
        'Moderator',
        'User',
        'ManageUsers1',
        'ManageUsers2'
    ]);
  }
  
  /**
   * @depends createAdditionalRoles
   */
  public function cannotMoveOutOfBounds(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Up"]');
    $I->click('#Move-4 button[value="Down"]');
    
    $this->verifyRoleOrder($I, [
        'Moderator',
        'User',
        'ManageUsers1',
        'ManageUsers2'
    ]);
  }
  
  /**
   * @depends moveTopRoleAround
   * @depends moveBottomRoleAround
   * @depends cannotMoveOutOfBounds
   */
  public function deleteRole(AcceptanceTester $I): void{
    $I->click('#Delete-3 button[type="submit"]');
    
    $this->verifyRoleOrder($I, [
        'Moderator',
        'User',
        'ManageUsers2'
    ]);
  }
  
  /**
   * @depends deleteRole
   */
  public function readdRole(AcceptanceTester $I): void{
    $this->createRole($I, 'ManageUsers1');
    $I->click('#Move-4 button[value="Up"]');
    
    $this->verifyRoleOrder($I, [
        'Moderator',
        'User',
        'ManageUsers1',
        'ManageUsers2'
    ]);
  }
}

?>
