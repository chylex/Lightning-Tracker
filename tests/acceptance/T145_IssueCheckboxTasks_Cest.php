<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T145_IssueCheckboxTasks_Cest{
  public function _after(AcceptanceTester $I): void{
    Acceptance::getDB()->exec('UPDATE issues SET milestone_id = NULL, description = \'\', status = \'open\', progress = 0 WHERE project_id = '.Acceptance::getProjectId($I, 'p1').' AND issue_id = 1');
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
  
  private function submitCheckBoxes(AcceptanceTester $I, array $task_status){
    foreach($task_status as $task => $checked){
      if ($checked){
        $I->checkOption('#Tasks-'.($task + 1));
      }
      else{
        $I->uncheckOption('#Tasks-'.($task + 1));
      }
    }
    
    $I->click('article[data-task-submit] button[type="submit"]');
  }
  
  private function validateCheckBoxes(AcceptanceTester $I, array $task_status){
    foreach($task_status as $task => $checked){
      if ($checked){
        $I->seeCheckboxIsChecked('#Tasks-'.($task + 1));
      }
      else{
        $I->dontSeeCheckboxIsChecked('#Tasks-'.($task + 1));
      }
    }
  }
  
  private function checkIssueDetailStatus(AcceptanceTester $I, string $status, int $progress): void{
    $I->see($status, '[data-title="Status"]');
    $I->see((string)$progress, '[data-title="Progress"] .progress-bar');
  }
  
  private function postAjaxTasks(AcceptanceTester $I, array $tasks): void{
    $I->sendAjaxPostRequest('/project/p1/issues/1', [
        '_Action' => 'Update',
        'Tasks'  => array_values(array_filter(array_map(fn($v, $id) => $v ? $id : null, $tasks, range(1, count($tasks))), fn($v): bool => $v !== null)),
    ]);
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
  public function checkingTasksUpdatesStatusAndProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([false, false, false]));
    $this->submitCheckBoxes($I, $example[0]);
    $this->checkIssueDetailStatus($I, $example[1], $example[2]);
    $this->validateCheckBoxes($I, $example[0]);
  }
  
  /**
   * @example [[false, false, false], "In Progress", 0]
   * @example [[true, true, false], "In Progress", 66]
   * @example [[true, true, true], "Ready To Test", 100]
   */
  public function updatingInProgressTasksUpdatesStatusAndProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, false, true]));
    $this->checkIssueDetailStatus($I, 'In Progress', 66);
    $this->submitCheckBoxes($I, $example[0]);
    $this->checkIssueDetailStatus($I, $example[1], $example[2]);
    $this->validateCheckBoxes($I, $example[0]);
  }
  
  /**
   * @example [[false, false, false], "In Progress", 0]
   * @example [[true, true, false], "In Progress", 66]
   * @example [[true, true, true], "Ready To Test", 100]
   */
  public function updatingReadyToTestTasksUpdatesStatusAndProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, true, true]));
    $this->checkIssueDetailStatus($I, 'Ready To Test', 100);
    $this->submitCheckBoxes($I, $example[0]);
    $this->checkIssueDetailStatus($I, $example[1], $example[2]);
    $this->validateCheckBoxes($I, $example[0]);
  }
  
  /**
   * @example [[false, false, false], 0]
   * @example [[true, true, false], 66]
   * @example [[true, true, true], 100]
   */
  public function updatingBlockedTasksUpdatesOnlyProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, false, true]), 'Blocked');
    $this->checkIssueDetailStatus($I, 'Blocked', 66);
    $this->submitCheckBoxes($I, $example[0]);
    $this->checkIssueDetailStatus($I, 'Blocked', $example[1]);
    $this->validateCheckBoxes($I, $example[0]);
  }
  
  /**
   * @example [[false, false, false], 0]
   * @example [[true, true, false], 66]
   * @example [[true, true, true], 100]
   */
  public function updatingFinishedTasksUpdatesOnlyProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, true, true]), 'Finished');
    $this->checkIssueDetailStatus($I, 'Finished', 100);
    $this->submitCheckBoxes($I, $example[0]);
    $this->checkIssueDetailStatus($I, 'Finished', $example[1]);
    $this->validateCheckBoxes($I, $example[0]);
  }
  
  /**
   * @example [[false, false, false], 0]
   * @example [[true, true, false], 66]
   * @example [[true, true, true], 100]
   */
  public function updatingRejectedTasksUpdatesOnlyProgress(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([true, true, true]), 'Rejected');
    $this->checkIssueDetailStatus($I, 'Rejected', 100);
    $this->submitCheckBoxes($I, $example[0]);
    $this->checkIssueDetailStatus($I, 'Rejected', $example[1]);
    $this->validateCheckBoxes($I, $example[0]);
  }
  
  /**
   * @example [[false, false, false], [false, false, false], "Open", 0]
   * @example [[false, false, false], [true, true, false], "In Progress", 66]
   * @example [[false, false, false], [true, true, true], "Ready To Test", 100]
   * @example [[false, false, true], [false, true, true], "In Progress", 66]
   * @example [[true, true, true], [false, false, false], "In Progress", 0]
   * @example [[true, true, true], [true, false, false], "In Progress", 33]
   * @example [[true, true, true], [true, true, true], "Ready To Test", 100]
   */
  public function updateCheckboxesViaAjax(AcceptanceTester $I, Example $example): void{
    $this->editDescriptionAs($I, 'User1', $this->generateDescription($example[0]));
    
    $I->haveHttpHeader('Accept', 'application/json');
    $this->postAjaxTasks($I, $example[1]);
    $I->seeResponseCodeIsSuccessful();
    $response = json_decode($I->grabPageSource(), true);
    
    $I->assertStringContainsString($example[2], $response['issue_status']);
    $I->assertEquals($example[3], $response['issue_progress']);
    $I->assertNull($response['active_milestone']);
  }
  
  /**
   * @example [[false, false, false], "Open", 0, 68]
   * @example [[true, false, false], "In Progress", 33, 78]
   * @example [[true, true, false], "In Progress", 66, 89]
   * @example [[true, true, true], "Ready To Test", 100, 100]
   */
  public function updateCheckboxesViaAjaxWithActiveMilestone(AcceptanceTester $I, Example $example): void{
    $project_id = Acceptance::getProjectId($I, 'p1');
    $db = Acceptance::getDB();
    $db->exec('UPDATE issues SET milestone_id = 2 WHERE project_id = '.$project_id.' AND issue_id = 1');
    $db->exec('INSERT INTO project_user_settings (project_id, user_id, active_milestone) VALUES ('.$project_id.', \'user1test\', 2) ON DUPLICATE KEY UPDATE active_milestone = VALUES(active_milestone)');
    $this->editDescriptionAs($I, 'User1', $this->generateDescription([false, false, false]));
    
    $I->haveHttpHeader('Accept', 'application/json');
    $this->postAjaxTasks($I, $example[0]);
    $I->seeResponseCodeIsSuccessful();
    $response = json_decode($I->grabPageSource(), true);
    
    $I->assertStringContainsString($example[1], $response['issue_status']);
    $I->assertEquals($example[2], $response['issue_progress']);
    $I->assertEquals($example[3], $response['active_milestone']);
    
    $db->exec('UPDATE project_user_settings SET active_milestone = NULL WHERE user_id = \'user1test\' AND project_id = '.$project_id);
  }
}

?>
