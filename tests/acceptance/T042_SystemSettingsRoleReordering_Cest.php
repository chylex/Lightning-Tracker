<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T042_SystemSettingsRoleReordering_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/settings/roles');
  }
  
  public function moveTopRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Down"]');
    
    $I->seeTableRowOrder(['Admin',
                          'ManageUsers1',
                          'Moderator',
                          'ManageUsers2',
                          'User',
                          'Test2',
                          'Test1']);
    
    $I->click('#Move-2 button[value="Down"]');
    
    $I->seeTableRowOrder(['Admin',
                          'ManageUsers1',
                          'ManageUsers2',
                          'Moderator',
                          'User',
                          'Test2',
                          'Test1']);
    
    $I->click('#Move-3 button[value="Up"]');
    $I->click('#Move-2 button[value="Up"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test2',
                          'Test1']);
  }
  
  public function moveBottomRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-6 button[value="Up"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test1',
                          'Test2']);
    
    $I->click('#Move-5 button[value="Up"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'Test1',
                          'User',
                          'Test2']);
    
    $I->click('#Move-4 button[value="Down"]');
    $I->click('#Move-5 button[value="Down"]');
    
    $I->seeTableRowOrder(['Admin',
                          'Moderator',
                          'ManageUsers1',
                          'ManageUsers2',
                          'User',
                          'Test2',
                          'Test1']);
  }
  
  public function cannotMoveOutOfBounds(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Up"]');
    $I->click('#Move-6 button[value="Down"]');
    
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
