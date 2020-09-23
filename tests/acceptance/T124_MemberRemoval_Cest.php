<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T124_MemberRemoval_Cest{
  private array $members;
  
  public function _before(): void{
    if (!isset($this->members)){
      $this->members = Acceptance::getDB()->query('SELECT * FROM project_members WHERE project_id = (SELECT id FROM projects WHERE url = \'p1\')')->fetchAll();
    }
  }
  
  private function restoreMembers(): void{
    $db = Acceptance::getDB();
    
    foreach($this->members as $member){
      $db->prepare('INSERT INTO project_members (project_id, user_id, role_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role_id = VALUES(role_id)')
         ->execute([$member['project_id'], $member['user_id'], $member['role_id']]);
    }
  }
  
  private function startRemovingAs(AcceptanceTester $I, string $editor, string $user): string{
    $stmt = Acceptance::getDB()->prepare('SELECT id FROM users WHERE name = ?');
    $stmt->execute([$user]);
    
    $id = $stmt->fetchColumn();
    $I->assertNotFalse($id);
    
    $I->amLoggedIn($editor);
    $I->amOnPage('/project/p1/members/'.$id.'/remove');
    
    return $id;
  }
  
  private function ensureCanRemoveWithoutConfirmation(AcceptanceTester $I, string $editor, string $user): void{
    $this->startRemovingAs($I, $editor, $user);
    $I->dontSee('Permission Error', 'h2');
    $I->seeCurrentUrlEquals('/project/p1/members');
  }
  
  private function ensureCanRemoveWithConfirmation(AcceptanceTester $I, string $editor, string $user): void{
    $id = $this->startRemovingAs($I, $editor, $user);
    $I->dontSee('Permission Error', 'h2');
    $I->seeCurrentUrlEquals('/project/p1/members/'.$id.'/remove');
  }
  
  private function ensureCannotRemove(AcceptanceTester $I, string $editor, string $user): void{
    $this->startRemovingAs($I, $editor, $user);
    $I->see('Permission Error', 'h2');
  }
  
  private function removeRoleLessWithConfirmation(AcceptanceTester $I, string $reassign): string{
    $id = $this->startRemovingAs($I, 'User1', 'RoleLess');
    $I->see('1 issue assigned');
    $I->selectOption('Reassign', $reassign);
    $I->click('button[type="submit"]');
    
    $I->seeTableRowOrder(['User1',
                          'Manager1',
                          'Manager2',
                          'User2',
                          'User3']);
    
    return $id;
  }
  
  private function restoreRoleLessIssueAssignment(string $id): void{
    Acceptance::getDB()->prepare('UPDATE issues SET assignee_id = ? WHERE title = \'Assigned Test Issue 8 (Feature)\' AND author_id = \'user1test\'')->execute([$id]);
  }
  
  public function nonExistentUser(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/members/000-000-000/remove');
    $I->see('Member not found');
  }
  
  public function nonExistentMember(AcceptanceTester $I): void{
    $this->startRemovingAs($I, 'User1', 'Admin');
    $I->see('Member not found');
  }
  
  public function trackerAdminCanRemoveAllButOwnerRoleDespiteNotBeingAMember(AcceptanceTester $I): void{
    $this->ensureCannotRemove($I, 'Admin', 'User1');
    $this->ensureCanRemoveWithConfirmation($I, 'Admin', 'Manager1');
    $this->ensureCanRemoveWithConfirmation($I, 'Admin', 'Manager2');
    $this->ensureCanRemoveWithoutConfirmation($I, 'Admin', 'User2');
    $this->ensureCanRemoveWithConfirmation($I, 'Admin', 'RoleLess');
    $this->ensureCanRemoveWithoutConfirmation($I, 'Admin', 'User3');
    $this->restoreMembers();
  }
  
  public function trackerModeratorCanRemoveAllButOwnerRoleDespiteNotBeingAMember(AcceptanceTester $I): void{
    $this->ensureCannotRemove($I, 'Moderator', 'User1');
    $this->ensureCanRemoveWithConfirmation($I, 'Moderator', 'Manager1');
    $this->ensureCanRemoveWithConfirmation($I, 'Moderator', 'Manager2');
    $this->ensureCanRemoveWithoutConfirmation($I, 'Moderator', 'User2');
    $this->ensureCanRemoveWithConfirmation($I, 'Moderator', 'RoleLess');
    $this->ensureCanRemoveWithoutConfirmation($I, 'Moderator', 'User3');
    $this->restoreMembers();
  }
  
  public function ownerCanRemoveAllButSelf(AcceptanceTester $I): void{
    $this->ensureCannotRemove($I, 'User1', 'User1');
    $this->ensureCanRemoveWithConfirmation($I, 'User1', 'Manager1');
    $this->ensureCanRemoveWithConfirmation($I, 'User1', 'Manager2');
    $this->ensureCanRemoveWithoutConfirmation($I, 'User1', 'User2');
    $this->ensureCanRemoveWithConfirmation($I, 'User1', 'RoleLess');
    $this->ensureCanRemoveWithoutConfirmation($I, 'User1', 'User3');
    $this->restoreMembers();
  }
  
  public function memberWithAdministratorRoleCanOnlyRemoveLowerRoles(AcceptanceTester $I): void{
    $this->ensureCannotRemove($I, 'Manager1', 'User1');
    $this->ensureCannotRemove($I, 'Manager1', 'Manager1');
    $this->ensureCanRemoveWithConfirmation($I, 'Manager1', 'Manager2');
    $this->ensureCanRemoveWithoutConfirmation($I, 'Manager1', 'User2');
    $this->ensureCanRemoveWithConfirmation($I, 'Manager1', 'RoleLess');
    $this->ensureCanRemoveWithoutConfirmation($I, 'Manager1', 'User3');
    $this->restoreMembers();
  }
  
  public function memberWithModeratorRoleCanOnlyRemoveLowerRoles(AcceptanceTester $I): void{
    $this->ensureCannotRemove($I, 'Manager2', 'User1');
    $this->ensureCannotRemove($I, 'Manager2', 'Manager1');
    $this->ensureCannotRemove($I, 'Manager2', 'Manager2');
    $this->ensureCanRemoveWithoutConfirmation($I, 'Manager2', 'User2');
    $this->ensureCanRemoveWithConfirmation($I, 'Manager2', 'RoleLess');
    $this->ensureCanRemoveWithoutConfirmation($I, 'Manager2', 'User3');
    $this->restoreMembers();
  }
  
  public function removeWithoutConfirmation(AcceptanceTester $I): void{
    $this->startRemovingAs($I, 'User1', 'User2');
    $I->seeCurrentUrlEquals('/project/p1/members');
    
    $I->seeTableRowOrder(['User1',
                          'Manager1',
                          'Manager2',
                          'RoleLess',
                          'User3']);
    
    $this->restoreMembers();
  }
  
  public function removeWithConfirmationKeepingAssignment(AcceptanceTester $I): void{
    $id = $this->removeRoleLessWithConfirmation($I, '(Do Not Reassign)');
    $I->seeInDatabase('issues', ['title' => 'Assigned Test Issue 8 (Feature)', 'author_id' => 'user1test', 'assignee_id' => $id]);
    $this->restoreMembers();
  }
  
  public function removeWithConfirmationReassigningToNobody(AcceptanceTester $I): void{
    $id = $this->removeRoleLessWithConfirmation($I, '(Reassign To Nobody)');
    $I->seeInDatabase('issues', ['title' => 'Assigned Test Issue 8 (Feature)', 'author_id' => 'user1test', 'assignee_id' => null]);
    $this->restoreMembers();
    $this->restoreRoleLessIssueAssignment($id);
  }
  
  public function removeWithConfirmationReassigningToUser2(AcceptanceTester $I): void{
    $id = $this->removeRoleLessWithConfirmation($I, 'User2');
    $I->seeInDatabase('issues', ['title' => 'Assigned Test Issue 8 (Feature)', 'author_id' => 'user1test', 'assignee_id' => 'user2test']);
    $this->restoreMembers();
    $this->restoreRoleLessIssueAssignment($id);
  }
}

?>
