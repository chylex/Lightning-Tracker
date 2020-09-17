<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T120_MemberList_Cest{
  private function viewProject(AcceptanceTester $I, int $project): void{
    $I->amLoggedIn('User'.$project);
    $I->amOnPage('/project/p'.$project.'/members');
  }
  
  private function inviteUser(AcceptanceTester $I, string $name, string $role): void{
    $this->viewProject($I, 1);
    $I->fillField('#Invite-1-Name', $name);
    $I->selectOption('#Invite-1-Role', $role);
    $I->click('#Invite-1 button[type="submit"]');
  }
  
  public function userDoesNotExist(AcceptanceTester $I): void{
    $this->viewProject($I, 1);
    $this->inviteUser($I, 'InvalidUser', '(Default)');
    $I->see('not found', '#Invite-1-Name + .error');
  }
  
  public function userIsTheOwner(AcceptanceTester $I): void{
    $this->viewProject($I, 1);
    $this->inviteUser($I, 'User1', '(Default)');
    $I->see('is the owner', '#Invite-1-Name + .error');
  }
  
  public function userIsAlreadyAMember(AcceptanceTester $I): void{
    $this->viewProject($I, 1);
    $this->inviteUser($I, 'User2', '(Default)');
    $I->see('is already a member', '#Invite-1-Name + .error');
  }
  
  public function testMembersOrderedByRoleAscThenNameAscIsDefaultInProject1(AcceptanceTester $I): void{
    $this->viewProject($I, 1);
    
    $I->seeTableRowOrder(['User1',
                          'Manager1',
                          'Manager2',
                          'User2',
                          'RoleLess',
                          'User3']);
  }
  
  public function testMembersOrderedByRoleAscThenNameAscIsDefaultInProject2(AcceptanceTester $I): void{
    $this->viewProject($I, 2);
    
    $I->seeTableRowOrder(['User2',
                          'Manager1',
                          'Manager2',
                          'User1']);
  }
  
  public function testMembersOrderedByNameInProject1(AcceptanceTester $I): void{
    $order = [
        'Manager1',
        'Manager2',
        'RoleLess',
        'User1',
        'User2',
        'User3',
    ];
    
    $this->viewProject($I, 1);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
  
  public function testMembersOrderedByNameInProject2(AcceptanceTester $I): void{
    $order = [
        'Manager1',
        'Manager2',
        'User1',
        'User2',
    ];
    
    $this->viewProject($I, 2);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
  
  public function testMembersOrderedByRoleInProject1(AcceptanceTester $I): void{
    $this->viewProject($I, 1);
    
    $I->click('thead tr:first-child th:nth-child(2) > a');
    
    $I->seeTableRowOrder(['User1',    // owner role
                          'Manager1', // role 1
                          'Manager2', // role 2
                          'User2',    // role 3
                          'RoleLess', // no role, alphabetically first
                          'User3']);  // no role, alphabetically last
    
    $I->click('thead tr:first-child th:nth-child(2) > a');
    
    $I->seeTableRowOrder(['RoleLess', // no role, alphabetically first
                          'User3',    // no role, alphabetically last
                          'User2',    // role 3
                          'Manager2', // role 2
                          'Manager1', // role 1
                          'User1']);  // owner role
  }
  
  public function testMembersOrderedByRoleInProject2(AcceptanceTester $I): void{
    $this->viewProject($I, 2);
    
    $I->click('thead tr:first-child th:nth-child(2) > a');
    
    $I->seeTableRowOrder(['User2',    // owner role
                          'Manager1', // role 1, alphabetically first
                          'Manager2', // role 1, alphabetically second
                          'User1']);  // role 1, alphabetically last
    
    $I->click('thead tr:first-child th:nth-child(2) > a');
    
    $I->seeTableRowOrder(['Manager1', // role 1, alphabetically first
                          'Manager2', // role 1, alphabetically second
                          'User1',    // role 1, alphabetically last
                          'User2']);  // owner role
  }
  
  /**
   * @depends testMembersOrderedByRoleInProject2
   */
  public function testMembersOrderedByRoleThenNameInProject2(AcceptanceTester $I): void{
    $this->viewProject($I, 2);
    
    $I->click('thead tr:first-child th:nth-child(2) > a');
    $I->click('thead tr:first-child th:nth-child(1) > a');
    
    $I->seeTableRowOrder(['User2',    // owner role
                          'Manager1', // role 1, alphabetically first
                          'Manager2', // role 1, alphabetically second
                          'User1']);  // role 1, alphabetically last
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    
    $I->seeTableRowOrder(['User2',      // owner role
                          'User1',      // role 1, alphabetically last
                          'Manager2',   // role 1, alphabetically second
                          'Manager1']); // role 1, alphabetically first
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->click('thead tr:first-child th:nth-child(2) > a');
    $I->click('thead tr:first-child th:nth-child(1) > a');
    
    $I->seeTableRowOrder(['Manager1', // role 1, alphabetically first
                          'Manager2', // role 1, alphabetically second
                          'User1',    // role 1, alphabetically last
                          'User2']);  // owner role
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    
    $I->seeTableRowOrder(['User1',    // role 1, alphabetically last
                          'Manager2', // role 1, alphabetically second
                          'Manager1', // role 1, alphabetically first
                          'User2']);  // owner role
  }
}

?>
