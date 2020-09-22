<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T123_MemberEditing_Cest{
  private const ROLES = [
      'Owner'         => 1,
      'Administrator' => 2,
      'Moderator'     => 3,
      'Developer'     => 4,
      'Reporter'      => 5,
  ];
  
  private function startEditingAs(AcceptanceTester $I, string $editor, string $user): void{
    $stmt = Acceptance::getDB()->prepare('SELECT id FROM users WHERE name = ?');
    $stmt->execute([$user]);
    
    $id = $stmt->fetchColumn();
    $I->assertNotFalse($id);
    
    $I->amLoggedIn($editor);
    $I->amOnPage('/project/p1/members/'.$id);
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function ensureCanOnlySetRoles(AcceptanceTester $I, array $roles): void{
    foreach($roles as $role){
      $I->see($role, '#Confirm-1-Role');
    }
    
    $missing = array_diff(array_keys(self::ROLES), $roles);
    
    foreach($missing as $role){
      $I->dontSee($role, '#Confirm-1-Role');
    }
    
    foreach($missing as $role){
      $I->submitForm('#Confirm-1', [
          'Role' => self::ROLES[$role],
      ]);
      
      $I->dontSeeCurrentUrlEquals('/project/p1/members');
      $I->seeElement('#Confirm-1-Role + .error');
    }
  }
  
  /**
   * @example ["User1", "Manager1", "Administrator"]
   * @example ["User1", "Manager2", "Moderator"]
   * @example ["User1", "User2", "Reporter"]
   * @example ["User1", "RoleLess", "(Default)"]
   * @example ["Admin", "RoleLess", "(Default)"]
   * @example ["Manager1", "RoleLess", "(Default)"]
   * @example ["Manager2", "RoleLess", "(Default)"]
   */
  public function fieldsArePrefilledCorrectly(AcceptanceTester $I, Example $example): void{
    $this->startEditingAs($I, $example[0], $example[1]);
    $I->seeOptionIsSelected('Role', $example[2]);
  }
  
  public function adminCanSetAllButOwnerRoleDespiteNotBeingAMember(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'Admin', 'RoleLess');
    
    $this->ensureCanOnlySetRoles($I, ['Administrator',
                                      'Moderator',
                                      'Developer',
                                      'Reporter']);
  }
  
  public function ownerCanSetAllButOwnerRole(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'User1', 'RoleLess');
    
    $this->ensureCanOnlySetRoles($I, ['Administrator',
                                      'Moderator',
                                      'Developer',
                                      'Reporter']);
  }
  
  public function memberWithAdministratorRoleCanOnlySetLowerRoles(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'Manager1', 'RoleLess');
    
    $this->ensureCanOnlySetRoles($I, ['Moderator',
                                      'Developer',
                                      'Reporter']);
  }
  
  public function memberWithModeratorRoleCanOnlySetLowerRoles(AcceptanceTester $I): void{
    $this->startEditingAs($I, 'Manager2', 'RoleLess');
    
    $this->ensureCanOnlySetRoles($I, ['Developer',
                                      'Reporter']);
  }
  
  /**
   * @example ["User3", "Moderator"]
   * @example ["User3", "(Default)"]
   */
  public function editTestMember(AcceptanceTester $I, Example $example): void{
    $this->startEditingAs($I, 'User1', $example[0]);
    $I->selectOption('Role', $example[1]);
    $I->click('button[type="submit"]');
    
    $this->startEditingAs($I, 'User1', $example[0]);
    $I->seeOptionIsSelected('Role', $example[1]);
  }
}

?>
