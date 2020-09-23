<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T134_MilestoneDeletion_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User2');
    $I->amOnPage('/project/p2/milestones');
    $I->fillField('#Create-1-Title', 'Test');
    $I->click('#Create-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['Milestone',
                          'Second Milestone',
                          'Third Milestone',
                          'Fourth Milestone',
                          'Test']);
  }
  
  private function assignIssueToTestMilestoneAndBeginDeletion(AcceptanceTester $I): void{
    $db = Acceptance::getDB();
    $id = $db->query('SELECT milestone_id FROM milestones WHERE title = \'Test\'')->fetchColumn();
    
    $I->assertNotFalse($id);
    $I->assertIsNumeric($id);
    
    $db->prepare('UPDATE issues SET milestone_id = ? WHERE title = \'Issue 1 (Feature)\'')->execute([$id]);
    $I->amOnPage('/project/p2/milestones/'.$id.'/delete');
  }
  
  public function deleteUnassignedMilestoneWithoutConfirmation(AcceptanceTester $I): void{
    $I->click('tbody tr:last-child form[action$="/delete"] button[type="submit"]');
    $I->seeCurrentUrlEquals('/project/p2/milestones');
    
    $I->seeTableRowOrder(['Milestone',
                          'Second Milestone',
                          'Third Milestone',
                          'Fourth Milestone']);
  }
  
  public function deleteWithUnassignment(AcceptanceTester $I): void{
    $this->assignIssueToTestMilestoneAndBeginDeletion($I);
    $I->selectOption('Replacement', '(No Milestone)');
    $I->click('button[type="submit"]');
    $I->seeInDatabase('issues', ['title' => 'Issue 1 (Feature)', 'milestone_id' => null]);
  }
  
  public function deleteWithReassignment(AcceptanceTester $I): void{
    $this->assignIssueToTestMilestoneAndBeginDeletion($I);
    $I->selectOption('Replacement', 'Milestone');
    $I->click('button[type="submit"]');
    $I->seeInDatabase('issues', ['title' => 'Issue 1 (Feature)', 'milestone_id' => 1]);
    
    Acceptance::getDB()->exec('UPDATE issues SET milestone_id = NULL WHERE title = \'Issue 1 (Feature)\'');
  }
}

?>
