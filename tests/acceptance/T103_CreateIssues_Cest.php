<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T103_CreateIssues_Cest{
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function createIssueFull(AcceptanceTester $I,
                                   int $project,
                                   string $user,
                                   string $type,
                                   string $title,
                                   string $description,
                                   string $priority = 'Medium',
                                   string $scale = 'Medium',
                                   string $status = 'Open',
                                   int $progress = 0,
                                   string $milestone = '(None)',
                                   string $assignee = '(None)'): void{
    $I->amLoggedIn($user);
    $I->amOnPage('/project/p'.$project.'/issues/new');
    $I->selectOption('Type', $type);
    $I->fillField('Title', $title);
    $I->fillField('Description', $description);
    $I->selectOption('Priority', $priority);
    $I->selectOption('Scale', $scale);
    $I->selectOption('Status', $status);
    $I->fillField('Progress', $progress);
    $I->selectOption('Milestone', $milestone);
    $I->selectOption('Assignee', $assignee);
    $I->click('button[type="submit"]');
  }
  
  private function createIssueLimited(AcceptanceTester $I, int $project, string $user, string $type, string $title, string $description): void{
    $I->amLoggedIn($user);
    $I->amOnPage('/project/p'.$project.'/issues/new');
    $I->selectOption('Type', $type);
    $I->fillField('Title', $title);
    $I->fillField('Description', $description);
    $I->click('button[type="submit"]');
  }
  
  /**
   * @example [null, null]
   * @example ["feature", "Feature"]
   * @example ["enhancement", "Enhancement"]
   * @example ["bug", "Bug"]
   * @example ["crash", "Crash"]
   * @example ["task", "Task"]
   */
  public function fieldsArePrefilledCorrectly(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/new'.($example[0] === null ? '' : '/'.$example[0]));
    
    if ($example[1] !== null){
      $I->seeOptionIsSelected('Type', $example[1]);
    }
    
    $I->seeOptionIsSelected('Priority', 'Medium');
    $I->seeOptionIsSelected('Scale', 'Medium');
    $I->seeOptionIsSelected('Status', 'Open');
    $I->seeInField('Progress', '0');
    $I->seeOptionIsSelected('Milestone', '(None)');
    $I->seeOptionIsSelected('Assignee', '(None)');
  }
  
  public function createIssuesInProject1(AcceptanceTester $I): void{
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 1 (Feature)', 'All default field values.');
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 2 (Feature)', 'Low priority and tiny scale.', 'Low', 'Tiny');
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 3 (Feature)', 'Medium priority and tiny scale.', 'Medium', 'Tiny');
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 4 (Feature)', 'High priority and tiny scale.', 'High', 'Tiny');
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 5 (Feature)', 'Low priority and small scale.', 'Low', 'Small');
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 6 (Feature)', 'Low priority, small scale, in progress status of 10.', 'Low', 'Small', 'In Progress', 10);
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 7 (Feature)', 'Low priority, large scale, in progress status of 20.', 'Low', 'Large', 'In Progress', 20);
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 8 (Feature)', 'Low priority, large scale, in progress status of 20 (again).', 'Low', 'Large', 'In Progress', 20);
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 9 (Feature)', 'High priority, massive scale, in progress status of 20.', 'High', 'Massive', 'In Progress', 20);
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 10 (Feature)', 'High priority, massive scale, blocked status with progress 50.', 'High', 'Massive', 'Blocked', 50);
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 11 (Feature)', 'High priority, massive scale, ready to test status.', 'High', 'Massive', 'Ready To Test', 100);
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 12 (Feature)', 'Medium priority, small scale, finished status.', 'Medium', 'Small', 'Finished', 100);
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 13 (Feature)', 'Medium priority, medium scale, rejected status.', 'Medium', 'Medium', 'Rejected', 100);
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Status Test Issue 14 (Feature)', 'Medium priority, large scale, rejected status.', 'Medium', 'Large', 'Rejected', 100);
    
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Milestone 1 Test Issue 1 (Feature)', '', 'Medium', 'Small', 'Finished', 100, 'Milestone');
    $this->createIssueFull($I, 1, 'User1', 'Bug', 'Milestone 1 Test Issue 2 (Bug)', '', 'High', 'Small', 'In Progress', 25, 'Milestone');
    
    $this->createIssueFull($I, 1, 'User1', 'Enhancement', 'Milestone 2 Test Issue 1 (Enhancement)', '', 'High', 'Tiny', 'Finished', 100, 'Milestone 2');
    $this->createIssueFull($I, 1, 'User1', 'Enhancement', 'Milestone 2 Test Issue 2 (Enhancement)', '', 'High', 'Medium', 'Finished', 100, 'Milestone 2');
    $this->createIssueFull($I, 1, 'User1', 'Enhancement', 'Milestone 2 Test Issue 3 (Enhancement)', '', 'Medium', 'Medium', 'Finished', 100, 'Milestone 2');
    
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Milestone 3 Test Issue 1 (Feature)', '', 'Low', 'Medium', 'Open', 0, 'Milestone 3');
    $this->createIssueFull($I, 1, 'User1', 'Enhancement', 'Milestone 3 Test Issue 2 (Enhancement)', '', 'Low', 'Massive', 'Open', 0, 'Milestone 3');
    $this->createIssueFull($I, 1, 'User1', 'Bug', 'Milestone 3 Test Issue 3 (Bug)', '', 'High', 'Massive', 'Blocked', 20, 'Milestone 3');
    
    $this->createIssueFull($I, 1, 'User1', 'Task', 'Assigned Test Issue 1 (Task)', '', 'Medium', 'Medium', 'Open', 0, 'Milestone 4', 'User1');
    $this->createIssueFull($I, 1, 'User1', 'Task', 'Assigned Test Issue 2 (Task)', '', 'Medium', 'Medium', 'In Progress', 0, 'Milestone 4', 'User1');
    $this->createIssueFull($I, 1, 'User1', 'Task', 'Assigned Test Issue 3 (Task)', '', 'High', 'Medium', 'In Progress', 33, 'Milestone 4', 'User1');
    $this->createIssueFull($I, 1, 'User1', 'Task', 'Assigned Test Issue 4 (Task)', '', 'High', 'Medium', 'Ready To Test', 100, '(None)', 'User1');
    $this->createIssueFull($I, 1, 'User1', 'Crash', 'Assigned Test Issue 5 (Crash)', '', 'High', 'Small', 'Ready To Test', 100, '(None)', 'User1');
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Assigned Test Issue 6 (Feature)', '', 'Low', 'Small', 'Blocked', 30, '(None)', 'Manager1');
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Assigned Test Issue 7 (Feature)', '', 'Low', 'Small', 'Blocked', 40, '(None)', 'Manager2');
    $this->createIssueFull($I, 1, 'User1', 'Feature', 'Assigned Test Issue 8 (Feature)', '', 'Low', 'Small', 'Blocked', 50, '(None)', 'RoleLess');
  }
  
  public function createIssuesInProject2(AcceptanceTester $I): void{
    $this->createIssueLimited($I, 2, 'Manager1', 'Feature', 'Issue 1 (Feature)', 'AAA [Feat]');
    $this->createIssueLimited($I, 2, 'Manager2', 'Feature', 'Issue 2 (Feature)', 'BBB [Feat]');
    
    $this->createIssueLimited($I, 2, 'Manager1', 'Enhancement', 'Issue 3 (Enhancement)', 'CCC [Enh]');
    $this->createIssueLimited($I, 2, 'Manager2', 'Enhancement', 'Issue 4 (Enhancement)', 'DDD [Enh]');
    
    $this->createIssueLimited($I, 2, 'Manager1', 'Bug', 'Issue 5 (Bug)', 'EEE [Bg]');
    
    $this->createIssueLimited($I, 2, 'Manager1', 'Crash', 'Issue 6 (Crash)', 'FFF [Csh]');
    
    $this->createIssueLimited($I, 2, 'Manager1', 'Task', 'Issue 7 (Task)', 'GGG [Tsk]');
    $this->createIssueLimited($I, 2, 'User1', 'Task', 'Issue 8 (Task)', 'HHH [Tsk]');
    $this->createIssueLimited($I, 2, 'Manager2', 'Task', 'Issue 9 (Task)', 'III [Tsk]');
    
    $this->createIssueFull($I, 2, 'User2', 'Task', 'Issue 10 (Task)', '', 'Medium', 'Medium', 'Blocked', 50, 'Milestone', 'Manager1');
  }
  
  /**
   * @depends createIssuesInProject1
   * @depends createIssuesInProject2
   * @noinspection SqlWithoutWhere
   */
  public function setupCreationDateOrder(): void{
    Acceptance::getDB()->exec('UPDATE issues SET date_created = DATE_SUB(NOW(), INTERVAL issue_id SECOND), date_updated = date_created');
  }
}

?>
