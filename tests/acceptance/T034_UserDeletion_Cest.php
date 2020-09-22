<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T034_UserDeletion_Cest{
  private function startDeletingAs(AcceptanceTester $I, string $editor, string $user): void{
    $stmt = Acceptance::getDB()->prepare('SELECT id FROM users WHERE name = ?');
    $stmt->execute([$user]);
    
    $id = $stmt->fetchColumn();
    $I->assertNotFalse($id);
    
    $I->amLoggedIn($editor);
    $I->amOnPage('/users/'.$id.'/delete');
  }
  
  private function ensureCanDelete(AcceptanceTester $I, string $editor, string $user): void{
    $this->startDeletingAs($I, $editor, $user);
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function ensureCannotDelete(AcceptanceTester $I, string $editor, string $user): void{
    $this->startDeletingAs($I, $editor, $user);
    $I->see('Permission Error', 'h2');
  }
  
  public function nonExistentUser(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/users/000-000-000/delete');
    $I->see('User not found');
  }
  
  public function adminCanDeleteAllButSelf(AcceptanceTester $I): void{
    $this->ensureCannotDelete($I, 'Admin', 'Admin');
    $this->ensureCanDelete($I, 'Admin', 'Moderator');
    $this->ensureCanDelete($I, 'Admin', 'Manager1');
    $this->ensureCanDelete($I, 'Admin', 'Manager2');
    $this->ensureCanDelete($I, 'Admin', 'User1');
    $this->ensureCanDelete($I, 'Admin', 'RoleLess');
  }
  
  public function moderatorCanOnlyDeleteLowerRoles(AcceptanceTester $I): void{
    $this->ensureCannotDelete($I, 'Moderator', 'Admin');
    $this->ensureCannotDelete($I, 'Moderator', 'Moderator');
    $this->ensureCanDelete($I, 'Moderator', 'Manager1');
    $this->ensureCanDelete($I, 'Moderator', 'Manager2');
    $this->ensureCanDelete($I, 'Moderator', 'User1');
    $this->ensureCanDelete($I, 'Moderator', 'RoleLess');
  }
  
  public function manager1CanOnlyDeleteLowerRoles(AcceptanceTester $I): void{
    $this->ensureCannotDelete($I, 'Manager1', 'Admin');
    $this->ensureCannotDelete($I, 'Manager1', 'Moderator');
    $this->ensureCannotDelete($I, 'Manager1', 'Manager1');
    $this->ensureCanDelete($I, 'Manager1', 'Manager2');
    $this->ensureCanDelete($I, 'Manager1', 'User1');
    $this->ensureCanDelete($I, 'Manager1', 'RoleLess');
  }
  
  public function manager2CanOnlyDeleteLowerRoles(AcceptanceTester $I): void{
    $this->ensureCannotDelete($I, 'Manager2', 'Admin');
    $this->ensureCannotDelete($I, 'Manager2', 'Moderator');
    $this->ensureCannotDelete($I, 'Manager2', 'Manager1');
    $this->ensureCannotDelete($I, 'Manager2', 'Manager2');
    $this->ensureCanDelete($I, 'Manager2', 'User1');
    $this->ensureCanDelete($I, 'Manager2', 'RoleLess');
  }
  
  public function confirmationDoesNotMatch(AcceptanceTester $I): void{
    $this->startDeletingAs($I, 'Admin', 'RoleLess');
    $I->fillField('Name', 'NotRoleLess');
    $I->click('button[type="submit"]');
    $I->seeElement('input[name="Name"] + .error');
  }
  
  public function confirmationIsCaseSensitive(AcceptanceTester $I): void{
    $this->startDeletingAs($I, 'Admin', 'RoleLess');
    $I->fillField('Name', 'RoleLesS');
    $I->click('button[type="submit"]');
    $I->seeElement('input[name="Name"] + .error');
  }
  
  public function deleteTestUser(AcceptanceTester $I): void{
    Acceptance::getDB()->exec('INSERT INTO users (id, name, email, password, role_id, date_registered) VALUES (\'aaabbbccc\', \'Test\', \'test@example.com\', \'\', NULL, NOW())');
    $I->seeInDatabase('users', ['name' => 'Test']);
    
    $this->startDeletingAs($I, 'Admin', 'Test');
    $I->fillField('Name', 'Test');
    $I->click('button[type="submit"]');
    
    $I->seeCurrentUrlEquals('/users');
    $I->dontSeeInDatabase('users', ['name' => 'Test']);
  }
}

?>
