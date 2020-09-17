<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T032_UserEditing_Cest{
  private const ROLES = [
      'Moderator',
      'ManageUsers1',
      'ManageUsers2',
      'User',
      'Admin',
  ];
  
  private function startEditingAs(AcceptanceTester $I, string $editor, string $user): void{
    $db = Acceptance::getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE name = ?');
    $stmt->execute([$user]);
    
    $id = $stmt->fetchColumn();
    $I->assertNotFalse($id);
    
    $I->amLoggedIn($editor);
    $I->amOnPage('/users/'.$id);
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function ensureCanOnlySetRoles(AcceptanceTester $I, array $roles): void{
    foreach($roles as $role){
      $I->see($role, '#Confirm-1-Role');
    }
    
    foreach(array_diff(self::ROLES, $roles) as $role){
      $I->dontSee($role, '#Confirm-1-Role');
    }
  }
  
  /**
   * @example ["Admin", "User1", "user1@example.com", "User"]
   * @example ["Admin", "User2", "user2@example.com", "User"]
   * @example ["Admin", "RoleLess", "role-less@example.com", "(None)"]
   * @example ["Moderator", "User1", "user1@example.com", "User"]
   * @example ["Manager1", "User1", "", "User"]
   * @example ["Manager2", "User1", "", "User"]
   */
  public function fieldsArePrefilledCorrectly(AcceptanceTester $I, Example $example): void{
    $this->startEditingAs($I, $example[0], $example[1]);
    $I->seeInField('Name', $example[1]);
    $I->seeInField('Email', $example[2]);
    $I->seeInField('Password', '');
    $I->seeOptionIsSelected('Role', $example[3]);
  }
  
  public function adminCanSetAllButSpecialRoles(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'Admin', 'RoleLess');
    
    $this->ensureCanOnlySetRoles($I, ['Moderator',
                                      'ManageUsers1',
                                      'ManageUsers2',
                                      'User']);
  }
  
  public function moderatorCanOnlySetLowerRoles(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'Moderator', 'RoleLess');
    
    $this->ensureCanOnlySetRoles($I, ['ManageUsers1',
                                      'ManageUsers2',
                                      'User']);
  }
  
  public function manager1CanOnlySetLowerRoles(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'Manager1', 'RoleLess');
    
    $this->ensureCanOnlySetRoles($I, ['ManageUsers2',
                                      'User']);
  }
  
  public function manager2CanOnlySetLowerRoles(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'Manager2', 'RoleLess');
    $this->ensureCanOnlySetRoles($I, ['User']);
  }
  
  /**
   * @depends fieldsArePrefilledCorrectly
   * @depends adminCanSetAllButSpecialRoles
   * @depends moderatorCanOnlySetLowerRoles
   * @depends manager1CanOnlySetLowerRoles
   * @depends manager2CanOnlySetLowerRoles
   */
  public function userAndEmailAlreadyExists(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'Admin', 'RoleLess');
    $I->fillField('Name', 'Moderator');
    $I->fillField('Email', 'admin@example.com');
    $I->fillField('Password', '111222333');
    $I->click('button[type="submit"]');
    
    $I->seeElement('#Confirm-1-Name + .error');
    $I->seeElement('#Confirm-1-Email + .error');
  }
  
  /**
   * @example ["RoleLess", "RoleLess2", "role-less-2@example.com", "abcdefghi", "Moderator"]
   * @example ["RoleLess2", "RoleLess", "role-less@example.com", "123456789", "(None)"]
   * @depends userAndEmailAlreadyExists
   */
  public function editTestUser(AcceptanceTester $I, Example $example): void{
    $this->startEditingAs($I, 'Admin', $example[0]);
    $I->fillField('Name', $example[1]);
    $I->fillField('Email', $example[2]);
    $I->fillField('Password', $example[3]);
    $I->selectOption('Role', $example[4]);
    $I->click('button[type="submit"]');
    
    $this->startEditingAs($I, 'Admin', $example[1]);
    $I->seeInField('Name', $example[1]);
    $I->seeInField('Email', $example[2]);
    $I->seeOptionIsSelected('Role', $example[4]);
  }
}

?>
