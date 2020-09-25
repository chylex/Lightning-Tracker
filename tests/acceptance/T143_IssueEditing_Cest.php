<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T143_IssueEditing_Cest{
  public function _after(): void{
    Acceptance::assignUser3Role('p1', null);
  }
  
  private function checkIssueDetailStatus(AcceptanceTester $I, string $status, int $progress): void{
    $I->see($status, '[data-title="Status"]');
    $I->see((string)$progress, '[data-title="Progress"] .progress-bar');
  }
  
  /**
   * @example ["User1", "Assigned Test Issue 2 (Task)"]
   * @example ["User3", "Assigned Test Issue 1 (Task)"]
   * @example ["Manager1", "Assigned Test Issue 6 (Feature)"]
   * @example ["Manager2", "Assigned Test Issue 7 (Feature)"]
   * @example ["RoleLess", "Assigned Test Issue 8 (Feature)"]
   */
  public function assigneeCanEditAllFields(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn($example[0]);
    $id = Acceptance::getIssueId($I, 'p1', $example[1]);
    
    $I->amOnPage('/project/p1/issues/'.$id);
    $I->seeElement('#MarkReadyToTest');
    $I->seeElement('#MarkFinished');
    $I->seeElement('#MarkRejected');
    
    $I->amOnPage('/project/p1/issues/'.$id.'/edit');
    $I->see('Priority', 'label');
    $I->see('Scale', 'label');
    $I->see('Progress', 'label');
    $I->see('Status', 'label');
    $I->see('Milestone', 'label');
    $I->see('Assignee', 'label');
  }
  
  /**
   * @example ["User3", "Milestone 1 Test Issue 1 (Feature)"]
   */
  public function authorWithoutPermissionsCanOnlyEditSomeFields(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn($example[0]);
    $id = Acceptance::getIssueId($I, 'p1', $example[1]);
    
    $I->amOnPage('/project/p1/issues/'.$id);
    $I->dontSeeElement('#MarkReadyToTest');
    $I->dontSeeElement('#MarkFinished');
    $I->dontSeeElement('#MarkRejected');
    
    $I->amOnPage('/project/p1/issues/'.$id.'/edit');
    $I->dontSee('Permission Error', 'h2');
    $I->see('Type', 'label');
    $I->see('Title', 'label');
    $I->see('Description', 'label');
    $I->dontSee('Priority', 'label');
    $I->dontSee('Scale', 'label');
    $I->dontSee('Progress', 'label');
    $I->dontSee('Status', 'label');
    $I->dontSee('Milestone', 'label');
    $I->dontSee('Assignee', 'label');
  }
  
  public function memberWithDeveloperRoleCanSeeAllMembersInAssigneeField(AcceptanceTester $I): void{
    Acceptance::assignUser3Role('p1', 'Developer');
    $I->amLoggedIn('User3');
    $id = Acceptance::getIssueId($I, 'p1', 'Status Test Issue 1 (Feature)');
    
    $I->amOnPage('/project/p1/issues/'.$id.'/edit');
    $I->see('Assignee', 'label');
    $I->see('(None)', '#Confirm-1-Assignee option');
    $I->see('User1', '#Confirm-1-Assignee option');
    $I->see('User2', '#Confirm-1-Assignee option');
    $I->see('User3', '#Confirm-1-Assignee option');
    $I->see('Manager1', '#Confirm-1-Assignee option');
    $I->see('Manager2', '#Confirm-1-Assignee option');
    $I->see('RoleLess', '#Confirm-1-Assignee option');
  }
  
  public function memberWithReporterRoleCannotSeeAllMembersInAssigneeField(AcceptanceTester $I): void{
    Acceptance::assignUser3Role('p1', 'Reporter');
    $I->amLoggedIn('User3');
    $id = Acceptance::getIssueId($I, 'p1', 'Assigned Test Issue 1 (Task)');
    
    $I->amOnPage('/project/p1/issues/'.$id.'/edit');
    $I->see('Assignee', 'label');
    $I->see('(None)', '#Confirm-1-Assignee option');
    $I->see('User3', '#Confirm-1-Assignee option');
    $I->dontSee('User1', '#Confirm-1-Assignee option');
    $I->dontSee('User2', '#Confirm-1-Assignee option');
    $I->dontSee('Manager1', '#Confirm-1-Assignee option');
    $I->dontSee('Manager2', '#Confirm-1-Assignee option');
    $I->dontSee('RoleLess', '#Confirm-1-Assignee option');
  }
  
  public function assigneeDoesNotExist(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/1/edit');
  
    $I->submitForm('#Confirm-1', [
        'Assignee' => '000000000'
    ]);
  
    $I->seeElement('#Confirm-1-Assignee + .error');
  }
  
  public function assigneeIsNotAMember(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/1/edit');
    $I->dontSee('Admin', '#Confirm-1-Assignee option');
    
    $I->submitForm('#Confirm-1', [
        'Assignee' => 'admintest'
    ]);
    
    $I->seeElement('#Confirm-1-Assignee + .error');
  }
  
  public function assigneeIsAFormerMember(AcceptanceTester $I): void{
    Acceptance::getDB()->exec('UPDATE issues SET assignee_id = \'admintest\' WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
    
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/1/edit');
    $I->see('Admin', '#Confirm-1-Assignee option');
    $I->click('button[type="submit"]');
    
    $I->seeCurrentUrlEquals('/project/p1/issues/1');
    $I->see('Admin', '[data-title="Assignee"]');
    
    Acceptance::getDB()->exec('UPDATE issues SET assignee_id = NULL WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
  }
  
  public function assigneeIsAFormerMemberAndGetsReassigned(AcceptanceTester $I): void{
    Acceptance::getDB()->exec('UPDATE issues SET assignee_id = \'admintest\' WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
    
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/1/edit');
    $I->see('Admin', '#Confirm-1-Assignee option');
    $I->selectOption('Assignee', '(None)');
    $I->click('button[type="submit"]');
    
    $I->seeCurrentUrlEquals('/project/p1/issues/1');
    $I->see('Nobody', '[data-title="Assignee"]');
    
    $I->amOnPage('/project/p1/issues/1/edit');
    $I->dontSee('Admin', '#Confirm-1-Assignee option');
  }
  
  /**
   * @example ["#MarkReadyToTest", "Ready to Test"]
   * @example ["#MarkFinished", "Finished"]
   * @example ["#MarkRejected", "Rejected"]
   */
  public function markIssueStatusFromNoProgress(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/1');
    $this->checkIssueDetailStatus($I, 'Open', 0);
    $I->submitForm($example[0], []);
    $this->checkIssueDetailStatus($I, $example[1], 100);
    
    Acceptance::getDB()->exec('UPDATE issues SET status = \'open\', progress = 0 WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
  }
  
  /**
   * @example ["#MarkReadyToTest", "Ready to Test"]
   * @example ["#MarkFinished", "Finished"]
   * @example ["#MarkRejected", "Rejected"]
   */
  public function markIssueStatusFromSomeProgress(AcceptanceTester $I, Example $example): void{
    Acceptance::getDB()->exec('UPDATE issues SET status = \'in-progress\', progress = 30 WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
    
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/1');
    $this->checkIssueDetailStatus($I, 'In Progress', 30);
    $I->submitForm($example[0], []);
    $this->checkIssueDetailStatus($I, $example[1], 100);
    
    Acceptance::getDB()->exec('UPDATE issues SET status = \'open\', progress = 0 WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
  }
  
  public function editIssue(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/1');
    
    $I->see('Feature', '[data-title="Type"]');
    $I->see('Medium', '[data-title="Priority"]');
    $I->see('Medium', '[data-title="Scale"]');
    $I->see('Open', '[data-title="Status"]');
    $I->see('0', '[data-title="Progress"] .progress-bar');
    $I->see('None', '[data-title="Milestone"]');
    $I->see('User1', '[data-title="Author"]');
    $I->see('Nobody', '[data-title="Assignee"]');
    
    $I->amOnPage('/project/p1/issues/1/edit');
    
    $I->selectOption('Type', 'enhancement');
    $I->selectOption('Priority', 'low');
    $I->selectOption('Scale', 'massive');
    $I->fillField('Title', 'Edited Status Test Issue 1 (Feature)');
    $I->fillField('Description', 'Edited Description');
    $I->selectOption('Status', 'in-progress');
    $I->fillField('Progress', '10');
    $I->selectOption('Milestone', 'Milestone 3');
    $I->selectOption('Assignee', 'User2');
    $I->click('button[type="submit"]');
    $I->amOnPage('/project/p1/issues/1');
    
    $I->see('Edited Status Test Issue 1 (Feature)', 'h2');
    $I->see('Edited Description', '.issue-description');
    $I->see('Enhancement', '[data-title="Type"]');
    $I->see('Low', '[data-title="Priority"]');
    $I->see('Massive', '[data-title="Scale"]');
    $I->see('In Progress', '[data-title="Status"]');
    $I->see('10', '[data-title="Progress"] .progress-bar');
    $I->see('Milestone 3', '[data-title="Milestone"]');
    $I->see('User1', '[data-title="Author"]');
    $I->see('User2', '[data-title="Assignee"]');
  }
  
  /**
   * @depends editIssue
   */
  public function revertIssueEdit(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/1/edit');
    
    $I->selectOption('Type', 'feature');
    $I->selectOption('Priority', 'medium');
    $I->selectOption('Scale', 'medium');
    $I->fillField('Title', 'Status Test Issue 1 (Feature)');
    $I->fillField('Description', '');
    $I->selectOption('Status', 'open');
    $I->fillField('Progress', '0');
    $I->selectOption('Milestone', '(None)');
    $I->selectOption('Assignee', '(None)');
    $I->click('button[type="submit"]');
    
    $I->amOnPage('/project/p1/issues/1');
    
    $I->dontSee('Edited Status Test Issue 1 (Feature)', 'h2');
    $I->dontSee('Edited Description', '.issue-description');
    $I->see('Feature', '[data-title="Type"]');
    $I->see('Medium', '[data-title="Priority"]');
    $I->see('Medium', '[data-title="Scale"]');
    $I->see('Open', '[data-title="Status"]');
    $I->see('0', '[data-title="Progress"] .progress-bar');
    $I->see('None', '[data-title="Milestone"]');
    $I->see('User1', '[data-title="Author"]');
    $I->see('Nobody', '[data-title="Assignee"]');
  }
}

?>
