<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T153_ProjectSettingsRoleReordering_Cest{
  private const DEFAULT_ORDER = [
      'Owner',
      'Administrator',
      'Moderator',
      'Developer',
      'Reporter',
      'Test2',
      'Test1',
  ];
  
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/settings/roles');
  }
  
  public function moveTopRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Down"]');
    
    $I->seeTableRowOrder(['Owner',
                          'Moderator',
                          'Administrator',
                          'Developer',
                          'Reporter',
                          'Test2',
                          'Test1']);
    
    $I->click('#Move-2 button[value="Down"]');
    
    $I->seeTableRowOrder(['Owner',
                          'Moderator',
                          'Developer',
                          'Administrator',
                          'Reporter',
                          'Test2',
                          'Test1']);
    
    $I->click('#Move-3 button[value="Up"]');
    $I->click('#Move-2 button[value="Up"]');
    
    $I->seeTableRowOrder(self::DEFAULT_ORDER);
  }
  
  public function moveBottomRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-6 button[value="Up"]');
    
    $I->seeTableRowOrder(['Owner',
                          'Administrator',
                          'Moderator',
                          'Developer',
                          'Reporter',
                          'Test1',
                          'Test2']);
    
    $I->click('#Move-5 button[value="Up"]');
    
    $I->seeTableRowOrder(['Owner',
                          'Administrator',
                          'Moderator',
                          'Developer',
                          'Test1',
                          'Reporter',
                          'Test2']);
    
    $I->click('#Move-4 button[value="Down"]');
    $I->click('#Move-5 button[value="Down"]');
    
    $I->seeTableRowOrder(self::DEFAULT_ORDER);
  }
  
  public function cannotMoveOutOfBounds(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Up"]');
    $I->click('#Move-6 button[value="Down"]');
    $I->seeTableRowOrder(self::DEFAULT_ORDER);
  }
  
  public function cannotMoveOwnerRole(AcceptanceTester $I): void{
    $I->fillField('#Move-1 input[type="hidden"][name="Ordering"]', 0);
    $I->click('#Move-1 button[value="Down"]');
    $I->seeTableRowOrder(self::DEFAULT_ORDER);
  }
}

?>
