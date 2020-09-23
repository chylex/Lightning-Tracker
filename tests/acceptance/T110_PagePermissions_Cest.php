<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T110_PagePermissions_Cest{
  private function visitPageAs(AcceptanceTester $I, ?string $user, string $project, string $page): string{
    $url = '/project/'.$project.'/'.$page;
    
    if ($user === null){
      $I->amNotLoggedIn();
    }
    else{
      $I->amLoggedIn($user);
    }
    
    $I->amOnPage($url);
    return $url;
  }
  
  private function ensureCanAccessPageAs(AcceptanceTester $I, ?string $user, string $project, string $page): void{
    $url = $this->visitPageAs($I, $user, $project, $page);
    
    if ($user === null){
      $I->seeCurrentUrlEquals($url);
    }
    
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function ensureLoginRedirect(AcceptanceTester $I, ?string $user, string $project, string $page): void{
    $this->visitPageAs($I, $user, $project, $page);
    
    if ($user === null){
      $I->seeCurrentUrlEquals('/project/'.$project.'/login?return='.rawurlencode($page));
    }
    else{
      $I->see('Permission Error', 'h2');
    }
  }
  
  private function ensurePermissionError(AcceptanceTester $I, ?string $user, string $project, string $page): void{
    $this->visitPageAs($I, $user, $project, $page);
    $I->see('Permission Error', 'h2');
  }
  
  private function ensureProjectError(AcceptanceTester $I, ?string $user, string $project, string $page): void{
    $this->visitPageAs($I, $user, $project, $page);
    $I->see('Project Error', 'h2');
  }
  
  /**
   * @example [null]
   * @example ["Admin"]
   * @example ["RoleLess"]
   */
  public function checkInvalidProject(AcceptanceTester $I, Example $example): void{
    foreach(['', 'issues', 'members', 'settings', 'delete'] as $page){
      $this->ensureProjectError($I, $example[0], 'InvalidProject', $page);
    }
  }
  
  /**
   * @example [null]
   * @example ["RoleLess"]
   */
  public function checkUnprivilegedPublicProject(AcceptanceTester $I, Example $example): void{
    $user = $example[0];
    
    $this->ensureCanAccessPageAs($I, $user, 'AdminVisible', '');
    $this->ensureCanAccessPageAs($I, $user, 'AdminVisible', 'issues');
    $this->ensureCanAccessPageAs($I, $user, 'AdminVisible', 'issues/1');
    $this->ensureCanAccessPageAs($I, $user, 'AdminVisible', 'milestones');
    
    $this->ensurePermissionError($I, $user, 'AdminVisible', 'members');
    
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'issues/new');
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'issues/1/edit');
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'issues/1/delete');
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'milestones/1');
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'milestones/1/delete');
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'members/000000000');
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'members/000000000/remove');
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'settings');
    $this->ensureLoginRedirect($I, $user, 'AdminVisible', 'delete');
  }
  
  /**
   * @example [null]
   * @example ["RoleLess"]
   */
  public function checkUnprivilegedHiddenProject(AcceptanceTester $I, Example $example): void{
    $user = $example[0];
    
    $this->ensureProjectError($I, $user, 'AdminHidden', '');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'issues');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'issues/new');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'issues/1');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'issues/1/edit');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'issues/1/delete');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'milestones');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'milestones/1');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'milestones/1/delete');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'members');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'members/000000000');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'members/000000000/remove');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'settings');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'settings/roles/1');
    $this->ensureProjectError($I, $user, 'AdminHidden', 'delete');
  }
  
  public function checkReporterRoleForProject1(AcceptanceTester $I): void{
    Acceptance::assignUser3Role('p1', 'Reporter');
    
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', '');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/new');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones');
    
    $this->ensurePermissionError($I, 'User3', 'p1', 'issues/1/edit');
    $this->ensurePermissionError($I, 'User3', 'p1', 'issues/1/delete');
    $this->ensurePermissionError($I, 'User3', 'p1', 'milestones/1');
    $this->ensurePermissionError($I, 'User3', 'p1', 'milestones/1/delete');
    $this->ensurePermissionError($I, 'User3', 'p1', 'members');
    $this->ensurePermissionError($I, 'User3', 'p1', 'members/000000000');
    $this->ensurePermissionError($I, 'User3', 'p1', 'members/000000000/remove');
    $this->ensurePermissionError($I, 'User3', 'p1', 'settings');
    $this->ensurePermissionError($I, 'User3', 'p1', 'settings/roles/1');
    $this->ensurePermissionError($I, 'User3', 'p1', 'delete');
  }
  
  public function checkDeveloperRoleForProject1(AcceptanceTester $I): void{
    Acceptance::assignUser3Role('p1', 'Developer');
    
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', '');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/new');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1/edit');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones/1');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones/1/delete');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'members');
    
    $this->ensurePermissionError($I, 'User3', 'p1', 'issues/1/delete');
    $this->ensurePermissionError($I, 'User3', 'p1', 'members/000000000');
    $this->ensurePermissionError($I, 'User3', 'p1', 'members/000000000/remove');
    $this->ensurePermissionError($I, 'User3', 'p1', 'settings');
    $this->ensurePermissionError($I, 'User3', 'p1', 'settings/roles/1');
    $this->ensurePermissionError($I, 'User3', 'p1', 'delete');
  }
  
  public function checkModeratorRoleForProject1(AcceptanceTester $I): void{
    Acceptance::assignUser3Role('p1', 'Moderator');
    
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', '');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/new');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1/edit');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1/delete');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones/1');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones/1/delete');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'members');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'members/000000000');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'members/000000000/remove');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'settings');
    
    $this->ensurePermissionError($I, 'User3', 'p1', 'settings/roles/1');
    $this->ensurePermissionError($I, 'User3', 'p1', 'delete');
  }
  
  public function checkAdministratorRoleForProject1(AcceptanceTester $I): void{
    Acceptance::assignUser3Role('p1', 'Administrator');
    
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', '');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/new');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1/edit');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'issues/1/delete');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones/1');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'milestones/1/delete');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'members');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'members/000000000');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'members/000000000/remove');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'settings');
    $this->ensureCanAccessPageAs($I, 'User3', 'p1', 'settings/roles/1');
    
    $this->ensurePermissionError($I, 'User3', 'p1', 'delete');
  }
  
  /**
   * @example ["User1", false]
   * @example ["Moderator", true]
   * @example ["Admin", true]
   */
  public function checkFullyPrivilegedForProject1(AcceptanceTester $I, Example $example): void{
    [$user, $can_delete] = $example;
    
    $this->ensureCanAccessPageAs($I, $user, 'p1', '');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'issues');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'issues/new');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'issues/1');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'issues/1/edit');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'issues/1/delete');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'milestones');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'milestones/1');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'milestones/1/delete');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'members');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'members/000000000');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'members/000000000/remove');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'settings');
    $this->ensureCanAccessPageAs($I, $user, 'p1', 'settings/roles/1');
    
    if ($can_delete){
      $this->ensureCanAccessPageAs($I, $user, 'p1', 'delete');
    }
    else{
      $this->ensurePermissionError($I, $user, 'p1', 'delete');
    }
  }
  
  /**
   * @depends checkReporterRoleForProject1
   * @depends checkDeveloperRoleForProject1
   * @depends checkModeratorRoleForProject1
   * @depends checkAdministratorRoleForProject1
   */
  public function resetUser3Role(): void{
    Acceptance::assignUser3Role('p1', null);
  }
}

?>
