<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T144_IssueDescriptionTasks_Cest{
  public function _after(AcceptanceTester $I): void{
    Acceptance::getDB()->exec('UPDATE issues SET author_id = \'user1test\', description = \'\', status = \'open\', progress = 0 WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
  }
  
  private function editDescriptionAs(AcceptanceTester $I, string $user, string $desc, ?string $status = null): void{
    $I->amLoggedIn($user);
    $I->amOnPage('/project/p1/issues/1/edit');
    $I->fillField('Description', $desc);
    
    if ($status !== null){
      $I->selectOption('Status', $status);
    }
    
    $I->click('button[type="submit"]');
    $I->seeCurrentUrlEquals('/project/p1/issues/1');
  }
  
  private function checkIssueDetailStatus(AcceptanceTester $I, string $status, int $progress): void{
    $I->see($status, '[data-title="Status"]');
    $I->see((string)$progress, '[data-title="Progress"] .progress-bar');
  }
  
  private function generateDescription(array $tasks): string{
    return implode("\n", array_map(fn($v, $k): string => ($v ? '[x]' : '[]').' Task '.$k, $tasks, range(1, count($tasks))));
  }
  
  /**
   * @example [[false, false, false], "Open", 0]
   * @example [[true, false, false], "In Progress", 33]
   * @example [[false, true, false], "In Progress", 33]
   * @example [[false, false, true], "In Progress", 33]
   * @example [[true, true, false], "In Progress", 66]
   * @example [[false, true, true], "In Progress", 66]
   * @example [[true, false, true], "In Progress", 66]
   * @example [[true, true, true], "Ready To Test", 100]
   */
  public function goingFromNoTasksToMultipleTasksUpdatesStatusAndProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]));
    $this->checkIssueDetailStatus($I, $example[1], $example[2]);
  }
  
  /**
   * @example [[false, false, false], "In Progress", 0]
   * @example [[true, true, false], "In Progress", 66]
   * @example [[true, true, true], "Ready To Test", 100]
   * @example [[true, true, true, false], "In Progress", 75]
   * @example [[true, true], "Ready To Test", 100]
   */
  public function updatingInProgressTasksUpdatesStatusAndProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, false, true]));
    $this->checkIssueDetailStatus($I, 'In Progress', 66);
  
    $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]));
    $this->checkIssueDetailStatus($I, $example[1], $example[2]);
  }
  
  /**
   * @example [[false, false, false], "In Progress", 0]
   * @example [[true, true, false], "In Progress", 66]
   * @example [[true, true, true], "Ready To Test", 100]
   * @example [[true, true, true, false], "In Progress", 75]
   * @example [[true, true], "Ready To Test", 100]
   */
  public function updatingReadyToTestTasksUpdatesStatusAndProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, true, true]));
    $this->checkIssueDetailStatus($I, 'Ready To Test', 100);
  
    $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]));
    $this->checkIssueDetailStatus($I, $example[1], $example[2]);
  }
  
  /**
   * @example [[false, false, false], 0]
   * @example [[true, true, false], 66]
   * @example [[true, true, true], 100]
   * @example [[true, true, true, false], 75]
   * @example [[true, true], 100]
   */
  public function updatingBlockedTasksUpdatesOnlyProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, false, true]), 'Blocked');
    $this->checkIssueDetailStatus($I, 'Blocked', 66);
    
    $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]));
    $this->checkIssueDetailStatus($I, 'Blocked', $example[1]);
  }
  
  /**
   * @example [[false, false, false], 0]
   * @example [[true, true, false], 66]
   * @example [[true, true, true], 100]
   * @example [[true, true, true, false], 75]
   * @example [[true, true], 100]
   */
  public function updatingFinishedTasksUpdatesOnlyProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, true, true]), 'Finished');
    $this->checkIssueDetailStatus($I, 'Finished', 100);
    
    $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]));
    $this->checkIssueDetailStatus($I, 'Finished', $example[1]);
  }
  
  /**
   * @example [[false, false, false], 0]
   * @example [[true, true, false], 66]
   * @example [[true, true, true], 100]
   * @example [[true, true, true, false], 75]
   * @example [[true, true], 100]
   */
  public function updatingRejectedTasksUpdatesOnlyProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, true, true]), 'Rejected');
    $this->checkIssueDetailStatus($I, 'Rejected', 100);
    
    $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]));
    $this->checkIssueDetailStatus($I, 'Rejected', $example[1]);
  }
  
  /**
   * @example [[true, false], "Open"]
   * @example [[true, false], "In Progress"]
   * @example [[true, false], "Ready To Test"]
   * @example [[true, false], "Finished"]
   * @example [[true, false], "Rejected"]
   * @example [[true, false], "Blocked"]
   */
  public function removingTasksDoesNotUpdateStatusAndProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]), $example[1]);
    
    if ($example[1] === 'Open'){
      // counts as unchanged status so it's automatically set to in-progress unlike other statuses
      $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]), $example[1]);
    }
    
    $this->checkIssueDetailStatus($I, $example[1], 50);
  
    $this->editDescriptionAs($I, 'User1', '');
    $this->checkIssueDetailStatus($I, $example[1], 50);
  }
  
  /**
   * @example [[true]]
   * @example [[false]]
   * @example [[true, true, false]]
   * @example [[true, true, true]]
   */
  public function memberWithReporterRoleCannotUpdateStatusAndProgressByCreatingTasks(AcceptanceTester $I, Example $example): void{
    Acceptance::getDB()->exec('UPDATE issues SET author_id = \'user3test\' WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
    $this->editDescriptionAs($I, 'User3', $this->generateDescription($example[0]));
    $this->checkIssueDetailStatus($I, 'Open', 0);
  }
  
  /**
   * @example [[]]
   * @example [[true]]
   * @example [[false]]
   * @example [[true, true, false]]
   * @example [[true, true, true]]
   */
  public function memberWithReporterRoleCannotUpdateStatusAndProgressByChangingTasks(AcceptanceTester $I, Example $example): void{
    Acceptance::getDB()->exec('UPDATE issues SET author_id = \'user3test\' WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, true, false]));
    $this->checkIssueDetailStatus($I, 'In Progress', 66);
    
    $this->editDescriptionAs($I, 'User3', $this->generateDescription($example[0]));
    $this->checkIssueDetailStatus($I, 'In Progress', 66);
  }
}

?>
