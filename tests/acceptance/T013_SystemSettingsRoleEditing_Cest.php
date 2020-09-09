<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T013_SystemSettingsRoleEditing_Cest{
  private const PERMS_ALL = [
      'settings',
      'projects.list',
      'projects.list.all',
      'projects.create',
      'projects.manage',
      'users.list',
      'users.see.emails',
      'users.create',
      'users.manage'
  ];
  
  private const PERMS_CHILD = [
      'projects.list.all',
      'projects.create',
      'projects.manage',
      'users.see.emails',
      'users.create',
      'users.manage'
  ];
  
  private const PERMS_FOR_ROLE_2 = [
      'users.list',
      'users.manage'
  ];
  
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/settings/roles');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function getCheckboxes(array $perms): array{
    return array_map(static fn($v): string => '#Confirm-1-Perm-'.str_replace('.', '_', $v), $perms);
  }
  
  private function startEditing(AcceptanceTester $I, string $title): void{
    $db = Acceptance::getDB();
    $stmt = $db->prepare('SELECT id FROM system_roles WHERE title = ?');
    $stmt->execute([$title]);
    
    $id = $stmt->fetchColumn();
    $I->assertNotFalse($id);
    $I->assertIsNumeric($id);
    $I->amOnPage('/settings/roles/'.$id);
  }
  
  private function assignPermissions(AcceptanceTester $I, string $title, array $perms, bool $should_be_successful): array{
    $this->startEditing($I, $title);
    
    $checkboxes = $this->getCheckboxes($perms);
    
    foreach($checkboxes as $cb){
      $I->checkOption($cb);
    }
    
    $I->click('button[type="submit"]');
    
    if ($should_be_successful){
      $I->seeCurrentUrlEquals('/settings/roles');
      $this->startEditing($I, $title);
    }
    else{
      $I->dontSeeCurrentUrlEquals('/settings/roles');
    }
    
    return $checkboxes;
  }
  
  public function renameAlreadyExists(AcceptanceTester $I): void{
    $this->startEditing($I, 'ManageUsers1');
    $I->fillField('#Confirm-1-Title', 'ManageUsers2');
    $I->click('button[type="submit"]');
    $I->seeElement('#Confirm-1-Title + .error');
  }
  
  public function renameAndRevert(AcceptanceTester $I): void{
    $this->startEditing($I, 'ManageUsers1');
    $I->fillField('#Confirm-1-Title', 'ManageUsers3');
    $I->click('button[type="submit"]');
    
    $this->startEditing($I, 'ManageUsers3');
    $I->fillField('#Confirm-1-Title', 'ManageUsers1');
    $I->click('button[type="submit"]');
  }
  
  public function verifyNoPermissionsChecked(AcceptanceTester $I): void{
    $this->startEditing($I, 'ManageUsers1');
    
    foreach($this->getCheckboxes(self::PERMS_ALL) as $cb){
      $I->dontSeeCheckboxIsChecked($cb);
    }
  }
  
  public function verifyPermissionDependencies(AcceptanceTester $I): void{
    $checkboxes = $this->assignPermissions($I, 'ManageUsers1', self::PERMS_CHILD, false);
    
    foreach($checkboxes as $cb){
      $I->seeElement($cb.' + div > .error');
    }
  }
  
  /**
   * @depends verifyNoPermissionsChecked
   * @depends verifyPermissionDependencies
   */
  public function setAllPermissionsForRole1(AcceptanceTester $I): void{
    $checkboxes = $this->assignPermissions($I, 'ManageUsers1', self::PERMS_ALL, true);
    
    foreach($checkboxes as $cb){
      $I->seeCheckboxIsChecked($cb);
    }
  }
  
  /**
   * @depends verifyNoPermissionsChecked
   * @depends verifyPermissionDependencies
   */
  public function setSomePermissionsForRole2(AcceptanceTester $I): void{
    $checkboxes = $this->assignPermissions($I, 'ManageUsers2', self::PERMS_FOR_ROLE_2, true);
    
    foreach($checkboxes as $cb){
      $I->seeCheckboxIsChecked($cb);
    }
    
    foreach(array_diff($this->getCheckboxes(self::PERMS_ALL), $checkboxes) as $cb){
      $I->dontSeeCheckboxIsChecked($cb);
    }
  }
}

?>
