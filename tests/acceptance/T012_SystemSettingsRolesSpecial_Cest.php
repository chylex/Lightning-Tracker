<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T012_SystemSettingsRolesSpecial_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/settings/roles');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function verifyRoleOrder(AcceptanceTester $I, array $roles): void{
    foreach($roles as $i => $role){
      $I->see($role, 'tbody tr:nth-child('.($i + 1).')');
    }
  }
  
  public function createSpecialRoles(AcceptanceTester $I): void{
    $db = Acceptance::getDB();
    $db->exec('INSERT INTO system_roles (title, ordering, special) VALUES (\'Special1\', 0, TRUE)');
    $db->exec('INSERT INTO system_roles (title, ordering, special) VALUES (\'Special2\', 0, TRUE)');
    
    $I->amOnPage('/settings/roles');
    
    $this->verifyRoleOrder($I, [
        'Special1',
        'Special2',
        'Moderator',
        'User',
        'ManageUsers1',
        'ManageUsers2'
    ]);
  }
  
  /**
   * @depends createSpecialRoles
   */
  public function cannotMoveRolesAboveSpecialRoles(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Up"]');
    
    $this->verifyRoleOrder($I, [
        'Special1',
        'Special2',
        'Moderator',
        'User',
        'ManageUsers1',
        'ManageUsers2'
    ]);
  }
  
  /**
   * @depends createSpecialRoles
   */
  public function cannotDeleteSpecialRole(AcceptanceTester $I): void{
    $db = Acceptance::getDB();
    $id = $db->query('SELECT id FROM system_roles WHERE special = TRUE LIMIT 1')->fetchColumn();
    
    $I->assertNotFalse($id);
    $I->assertIsNumeric($id);
    
    $I->fillField('#Delete-1 input[type="hidden"][name="Role"]', $id);
    $I->click('#Delete-1 button[type="submit"]');
    
    $this->verifyRoleOrder($I, [
        'Special1',
        'Special2',
        'Moderator',
        'User',
        'ManageUsers1',
        'ManageUsers2'
    ]);
  }
}

?>
