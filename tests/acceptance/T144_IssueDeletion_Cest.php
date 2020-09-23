<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T144_IssueDeletion_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
  }
  
  public function nonExistentIssue(AcceptanceTester $I): void{
    $I->amOnPage('/project/p1/issues/999/delete');
    $I->see('Issue Error');
  }
  
  public function invalidIssueId(AcceptanceTester $I): void{
    $I->amOnPage('/project/p1/issues/abc/delete');
    $I->see('Invalid issue ID');
  }
  
  public function confirmationIsEmpty(AcceptanceTester $I): void{
    $I->amOnPage('/project/p1/issues/1/delete');
    $I->fillField('Id', '');
    $I->click('button[type="submit"]');
    $I->seeElement('#Confirm-1-Id + .error');
  }
  
  public function confirmationDoesNotMatch(AcceptanceTester $I): void{
    $I->amOnPage('/project/p1/issues/1/delete');
    $I->fillField('Id', '2');
    $I->click('button[type="submit"]');
    $I->seeElement('#Confirm-1-Id + .error');
  }
  
  public function deleteIssue(AcceptanceTester $I): void{
    $db = Acceptance::getDB();
    $data = $db->query('SELECT * FROM issues WHERE issue_id = 1 AND project_id = (SELECT p.id FROM projects p WHERE p.url = \'p1\')')->fetchAll()[0];
    
    $I->amOnPage('/project/p1/issues/1/delete');
    $I->fillField('Id', '1');
    $I->click('button[type="submit"]');
    $I->seeCurrentUrlEquals('/project/p1/issues');
    
    $I->amOnPage('/project/p1/issues/1');
    $I->see('Issue Error');
    
    $columns = ['project_id',
                'issue_id',
                'author_id',
                'assignee_id',
                'milestone_id',
                'title',
                'description',
                'type',
                'priority',
                'scale',
                'status',
                'progress',
                'date_created',
                'date_updated'];
    
    /** @noinspection SqlInsertValues */
    $db->prepare('INSERT INTO issues ('.implode(',', $columns).') VALUES ('.implode(',', array_map(fn($ignore): string => '?', $columns)).')')
       ->execute(array_map(fn($column) => $data[$column], $columns));
    
    $I->amOnPage('/project/p1/issues/1');
    $I->dontSee('Issue Error');
  }
}

?>
