<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T013_SystemSettingsRolesSpecial_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/settings/roles');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  public function cannotDeleteAdminRole(AcceptanceTester $I): void{
    $db = Acceptance::getDB();
    $id = $db->query('SELECT id FROM system_roles WHERE type = \'admin\' LIMIT 1')->fetchColumn();
    
    $I->assertNotFalse($id);
    $I->assertIsNumeric($id);
    
    $I->fillField('#Delete-1 input[type="hidden"][name="Role"]', $id);
    $I->click('#Delete-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User']);
  }
}

?>
