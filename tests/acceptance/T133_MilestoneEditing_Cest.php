<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T133_MilestoneEditing_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/milestones/2');
  }
  
  public function fieldsArePrefilledCorrectly(AcceptanceTester $I): void{
    $I->seeInField('Title', 'Milestone 2');
  }
  
  public function titleIsEmpty(AcceptanceTester $I): void{
    $I->fillField('Title', '');
    $I->click('button[type="submit"]');
    $I->seeElement('#Confirm-1-Title + .error');
  }
  
  public function editMilestone(AcceptanceTester $I): void{
    $I->fillField('Title', 'Milestone 8');
    $I->click('button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 8',
                          'Milestone 3',
                          'Milestone 4']);
    
    $I->amOnPage('/project/p1/milestones/2');
    $I->fillField('Title', 'Milestone 2');
    $I->click('button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2',
                          'Milestone 3',
                          'Milestone 4']);
  }
}

?>
