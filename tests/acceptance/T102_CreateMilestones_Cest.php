<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T102_CreateMilestones_Cest{
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  public function createMilestonesInProject1(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/milestones');
    
    $I->fillField('#Create-1-Title', 'Milestone');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone']);
    
    $I->fillField('#Create-1-Title', 'Milestone 2');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2']);
    
    $I->fillField('#Create-1-Title', 'Milestone 3');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2',
                          'Milestone 3']);
    
    $I->fillField('#Create-1-Title', 'Milestone 4');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2',
                          'Milestone 3',
                          'Milestone 4']);
  }
  
  public function createMilestonesInProject2(AcceptanceTester $I): void{
    $I->amLoggedIn('User2');
    $I->amOnPage('/project/p2/milestones');
    
    $I->fillField('#Create-1-Title', 'Milestone');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone']);
    
    $I->fillField('#Create-1-Title', 'Second Milestone');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Second Milestone']);
    
    $I->fillField('#Create-1-Title', 'Third Milestone');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Second Milestone',
                          'Third Milestone']);
    
    $I->fillField('#Create-1-Title', 'Fourth Milestone');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Second Milestone',
                          'Third Milestone',
                          'Fourth Milestone']);
  }
}

?>
