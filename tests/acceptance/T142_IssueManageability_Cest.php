<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T142_IssueManageability_Cest{
  private function ensurePermissions(AcceptanceTester $I, int $issue_id, bool $can_edit, bool $can_delete): void{
    $I->amOnPage('/project/p1/issues/'.$issue_id);
    
    $perms = ["edit"   => $can_edit,
              "delete" => $can_delete];
    
    foreach($perms as $action => $allowed){
      if ($allowed){
        $I->seeElement('a[href$="/'.$action.'"]');
      }
      else{
        $I->dontSeeElement('a[href$="/'.$action.'"]');
      }
    }
    
    foreach($perms as $action => $allowed){
      $I->amOnPage('/project/p1/issues/'.$issue_id.'/'.$action);
      
      if ($allowed){
        $I->dontSee('Permission Error', 'h2');
      }
      else{
        $I->see('Permission Error', 'h2');
      }
    }
  }
  
  /**
   * @example ["Status Test Issue 1 (Feature)"]
   * @example ["Milestone 1 Test Issue 1 (Feature)"]
   * @example ["Assigned Test Issue 1 (Task)"]
   * @example ["Assigned Test Issue 2 (Task)"]
   * @example ["Assigned Test Issue 8 (Feature)"]
   */
  public function trackerAdminCanEditAndDeleteAllDespiteNotBeingAMember(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn('Admin');
    $this->ensurePermissions($I, Acceptance::getIssueId($I, 'p1', $example[0]), true, true);
  }
  
  /**
   * @example ["Status Test Issue 1 (Feature)"]
   * @example ["Milestone 1 Test Issue 1 (Feature)"]
   * @example ["Assigned Test Issue 1 (Task)"]
   * @example ["Assigned Test Issue 2 (Task)"]
   * @example ["Assigned Test Issue 8 (Feature)"]
   */
  public function trackerModeratorCanEditAndDeleteAllDespiteNotBeingAMember(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn('Moderator');
    $this->ensurePermissions($I, Acceptance::getIssueId($I, 'p1', $example[0]), true, true);
  }
  
  /**
   * @example ["Status Test Issue 1 (Feature)"]
   * @example ["Milestone 1 Test Issue 1 (Feature)"]
   * @example ["Assigned Test Issue 1 (Task)"]
   * @example ["Assigned Test Issue 2 (Task)"]
   * @example ["Assigned Test Issue 8 (Feature)"]
   */
  public function ownerCanEditAndDeleteAll(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn('User1');
    $this->ensurePermissions($I, Acceptance::getIssueId($I, 'p1', $example[0]), true, true);
  }
  
  /**
   * @example ["Status Test Issue 1 (Feature)"]
   * @example ["Milestone 1 Test Issue 1 (Feature)"]
   * @example ["Assigned Test Issue 1 (Task)"]
   * @example ["Assigned Test Issue 2 (Task)"]
   * @example ["Assigned Test Issue 8 (Feature)"]
   */
  public function memberWithAdministratorRoleCanEditAndDeleteAll(AcceptanceTester $I, Example $example): void{
    Acceptance::assignUser3Role('p1', 'Administrator');
    $I->amLoggedIn('User3');
    $this->ensurePermissions($I, Acceptance::getIssueId($I, 'p1', $example[0]), true, true);
  }
  
  /**
   * @example ["Status Test Issue 1 (Feature)"]
   * @example ["Milestone 1 Test Issue 1 (Feature)"]
   * @example ["Assigned Test Issue 1 (Task)"]
   * @example ["Assigned Test Issue 2 (Task)"]
   * @example ["Assigned Test Issue 8 (Feature)"]
   */
  public function memberWithModeratorRoleCanEditAndDeleteAll(AcceptanceTester $I, Example $example): void{
    Acceptance::assignUser3Role('p1', 'Moderator');
    $I->amLoggedIn('User3');
    $this->ensurePermissions($I, Acceptance::getIssueId($I, 'p1', $example[0]), true, true);
  }
  
  /**
   * @example ["Status Test Issue 1 (Feature)"]
   * @example ["Milestone 1 Test Issue 1 (Feature)"]
   * @example ["Assigned Test Issue 1 (Task)"]
   * @example ["Assigned Test Issue 2 (Task)"]
   * @example ["Assigned Test Issue 8 (Feature)"]
   */
  public function memberWithDeveloperRoleCanEditAllAndNotDeleteAny(AcceptanceTester $I, Example $example): void{
    Acceptance::assignUser3Role('p1', 'Developer');
    $I->amLoggedIn('User3');
    $this->ensurePermissions($I, Acceptance::getIssueId($I, 'p1', $example[0]), true, false);
  }
  
  /**
   * @example ["Status Test Issue 1 (Feature)", false]
   * @example ["Milestone 1 Test Issue 1 (Feature)", true]
   * @example ["Assigned Test Issue 1 (Task)", true]
   * @example ["Assigned Test Issue 2 (Task)", false]
   * @example ["Assigned Test Issue 8 (Feature)", false]
   */
  public function memberWithReporterRoleCanOnlyEditOwnedOrAssignedAndNotDeleteAny(AcceptanceTester $I, Example $example): void{
    Acceptance::assignUser3Role('p1', 'Reporter');
    $I->amLoggedIn('User3');
    $this->ensurePermissions($I, Acceptance::getIssueId($I, 'p1', $example[0]), $example[1], false);
  }
  
  /**
   * @example ["Status Test Issue 1 (Feature)", false]
   * @example ["Milestone 1 Test Issue 1 (Feature)", true]
   * @example ["Assigned Test Issue 1 (Task)", true]
   * @example ["Assigned Test Issue 2 (Task)", false]
   * @example ["Assigned Test Issue 8 (Feature)", false]
   */
  public function memberWithNoRoleCanOnlyEditOwnedOrAssignedAndNotDeleteAny(AcceptanceTester $I, Example $example): void{
    Acceptance::assignUser3Role('p1', null);
    $I->amLoggedIn('User3');
    $this->ensurePermissions($I, Acceptance::getIssueId($I, 'p1', $example[0]), $example[1], false);
  }
  
  /**
   * @depends memberWithAdministratorRoleCanEditAndDeleteAll
   * @depends memberWithModeratorRoleCanEditAndDeleteAll
   * @depends memberWithDeveloperRoleCanEditAllAndNotDeleteAny
   * @depends memberWithReporterRoleCanOnlyEditOwnedOrAssignedAndNotDeleteAny
   * @depends memberWithNoRoleCanOnlyEditOwnedOrAssignedAndNotDeleteAny
   */
  public function resetUser3Role(): void{
    Acceptance::assignUser3Role('p1', null);
  }
}

?>
