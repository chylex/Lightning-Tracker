<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T154_ProjectSettingsRoleEditing_Cest{
  private const PERMS_ALL = [
      'settings.view',
      'settings.manage.general',
      'settings.manage.description',
      'settings.manage.roles',
      'members.list',
      'members.manage',
      'milestones.manage',
      'issues.create',
      'issues.fields.all',
      'issues.edit.all',
      'issues.delete.all',
  ];
  
  private const PERMS_CHILD = [
      'settings.manage.general',
      'settings.manage.description',
      'settings.manage.roles',
      'members.manage',
  ];
  
  private const PERMS_SOME = [
      'members.list',
      'members.manage',
  ];
  
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/settings/roles');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function getCheckboxes(array $perms): array{
    return array_map(static fn($v): string => '#Confirm-1-Perm-'.str_replace('.', '_', $v), $perms);
  }
  
  private function startEditing(AcceptanceTester $I, string $title): void{
    $stmt = Acceptance::getDB()->prepare('SELECT role_id FROM project_roles WHERE title = ? AND project_id = '.Acceptance::getProjectId($I, 'p1'));
    $stmt->execute([$title]);
    
    $id = $stmt->fetchColumn();
    $I->assertNotFalse($id);
    $I->assertIsNumeric($id);
    $I->amOnPage('/project/p1/settings/roles/'.$id);
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function assignPermissions(AcceptanceTester $I, string $title, array $perms, bool $should_be_successful): array{
    $this->startEditing($I, $title);
    
    $checkboxes = $this->getCheckboxes($perms);
    
    foreach($checkboxes as $cb){
      $I->checkOption($cb);
    }
    
    $I->click('button[type="submit"]');
    
    if ($should_be_successful){
      $I->seeCurrentUrlEquals('/project/p1/settings/roles');
      $this->startEditing($I, $title);
    }
    else{
      $I->dontSeeCurrentUrlEquals('/project/p1/settings/roles');
    }
    
    return $checkboxes;
  }
  
  public function renameAlreadyExists(AcceptanceTester $I): void{
    $this->startEditing($I, 'Test1');
    $I->fillField('#Confirm-1-Title', 'Test2');
    $I->click('button[type="submit"]');
    $I->seeElement('#Confirm-1-Title + .error');
  }
  
  public function renameAndRevert(AcceptanceTester $I): void{
    $this->startEditing($I, 'Test1');
    $I->fillField('#Confirm-1-Title', 'Test3');
    $I->click('button[type="submit"]');
    
    $this->startEditing($I, 'Test3');
    $I->fillField('#Confirm-1-Title', 'Test1');
    $I->click('button[type="submit"]');
  }
  
  public function verifyNoPermissionsChecked(AcceptanceTester $I): void{
    $this->startEditing($I, 'Test1');
    
    foreach($this->getCheckboxes(self::PERMS_ALL) as $cb){
      $I->dontSeeCheckboxIsChecked($cb);
    }
  }
  
  public function verifyPermissionDependencies(AcceptanceTester $I): void{
    $checkboxes = $this->assignPermissions($I, 'Test1', self::PERMS_CHILD, false);
    
    foreach($checkboxes as $cb){
      $I->seeElement($cb.' + div > .error');
    }
  }
  
  /**
   * @depends verifyNoPermissionsChecked
   * @depends verifyPermissionDependencies
   */
  public function setAllPermissionsForRole1(AcceptanceTester $I): void{
    $checkboxes = $this->assignPermissions($I, 'Test1', self::PERMS_ALL, true);
    
    foreach($checkboxes as $cb){
      $I->seeCheckboxIsChecked($cb);
    }
  }
  
  /**
   * @depends verifyNoPermissionsChecked
   * @depends verifyPermissionDependencies
   */
  public function setSomePermissionsForRole2(AcceptanceTester $I): void{
    $checkboxes = $this->assignPermissions($I, 'Test2', self::PERMS_SOME, true);
    
    foreach($checkboxes as $cb){
      $I->seeCheckboxIsChecked($cb);
    }
    
    foreach(array_diff($this->getCheckboxes(self::PERMS_ALL), $checkboxes) as $cb){
      $I->dontSeeCheckboxIsChecked($cb);
    }
  }
  
  /**
   * @depends setAllPermissionsForRole1
   * @depends setSomePermissionsForRole2
   */
  public function cleanupTestRoles(): void{
    Acceptance::getDB()->exec('DELETE FROM system_roles WHERE title = \'Test1\' OR title = \'Test2\'');
  }
}

?>
