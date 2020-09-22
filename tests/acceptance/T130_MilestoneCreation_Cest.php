<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T130_MilestoneCreation_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/milestones');
  }
  
  private function createMilestone(AcceptanceTester $I, string $title): void{
    $I->fillField('#Create-1-Title', $title);
    $I->click('#Create-1 button[type="submit"]');
  }
  
  public function titleIsEmpty(AcceptanceTester $I): void{
    $this->createMilestone($I, '');
    $I->seeElement('#Create-1-Title + .error');
  }
  
  public function duplicateTitlesWork(AcceptanceTester $I): void{
    $this->createMilestone($I, 'Test Duplicate');
    $this->createMilestone($I, 'Test Duplicate');
    
    $I->seeTableRowOrder(['Milestone',
                          'Milestone 2',
                          'Milestone 3',
                          'Milestone 4',
                          'Test Duplicate',
                          'Test Duplicate']);
    
    Acceptance::getDB()->exec('DELETE FROM milestones WHERE title = \'Test Duplicate\'');
  }
}

?>
