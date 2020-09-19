<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T132_MilestoneReordering_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/milestones');
  }
  
  public function moveTopRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Down"]');
    
    $I->seeTableRowOrder(['Milestone 2',
                          'Milestone',
                          'Milestone 3',
                          'Milestone 4']);
    
    $I->click('#Move-2 button[value="Down"]');
    
    $I->seeTableRowOrder(['Milestone 2',
                          'Milestone 3',
                          'Milestone',
                          'Milestone 4']);
    
    $I->click('#Move-3 button[value="Up"]');
    $I->click('#Move-2 button[value="Up"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2',
                          'Milestone 3',
                          'Milestone 4']);
  }
  
  public function moveBottomRoleAround(AcceptanceTester $I): void{
    $I->click('#Move-4 button[value="Up"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2',
                          'Milestone 4',
                          'Milestone 3']);
    
    $I->click('#Move-3 button[value="Up"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 4',
                          'Milestone 2',
                          'Milestone 3']);
    
    $I->click('#Move-2 button[value="Down"]');
    $I->click('#Move-3 button[value="Down"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2',
                          'Milestone 3',
                          'Milestone 4']);
  }
  
  public function cannotMoveOutOfBounds(AcceptanceTester $I): void{
    $I->click('#Move-1 button[value="Up"]');
    $I->click('#Move-4 button[value="Down"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2',
                          'Milestone 3',
                          'Milestone 4']);
  }
}

?>
