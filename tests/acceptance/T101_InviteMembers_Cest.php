<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T101_InviteMembers_Cest{
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  public function inviteMembersInProject1(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/members');
    
    $I->fillField('#Invite-1-Name', 'User2');
    $I->selectOption('#Invite-1-Role', 'Reporter');
    $I->click('#Invite-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['User1',
                          'User2']);
    
    $I->fillField('#Invite-1-Name', 'RoleLess');
    $I->selectOption('#Invite-1-Role', '(Default)');
    $I->click('#Invite-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['User1',
                          'User2',
                          'RoleLess']);
    
    $I->fillField('#Invite-1-Name', 'Manager1');
    $I->selectOption('#Invite-1-Role', 'Administrator');
    $I->click('#Invite-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['User1',
                          'Manager1',
                          'User2',
                          'RoleLess']);
    
    $I->fillField('#Invite-1-Name', 'Manager2');
    $I->selectOption('#Invite-1-Role', 'Moderator');
    $I->click('#Invite-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['User1',
                          'Manager1',
                          'Manager2',
                          'User2',
                          'RoleLess']);
    
    $I->fillField('#Invite-1-Name', 'User3');
    $I->selectOption('#Invite-1-Role', '(Default)');
    $I->click('#Invite-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['User1',
                          'Manager1',
                          'Manager2',
                          'User2',
                          'RoleLess',
                          'User3']);
  }
  
  public function inviteMembersInProject2(AcceptanceTester $I): void{
    $I->amLoggedIn('User2');
    $I->amOnPage('/project/p2/members');
    
    $I->fillField('#Invite-1-Name', 'User1');
    $I->selectOption('#Invite-1-Role', 'Reporter');
    $I->click('#Invite-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['User2',
                          'User1']);
    
    $I->fillField('#Invite-1-Name', 'Manager1');
    $I->selectOption('#Invite-1-Role', 'Reporter');
    $I->click('#Invite-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['User2',
                          'Manager1',
                          'User1']);
    
    $I->fillField('#Invite-1-Name', 'Manager2');
    $I->selectOption('#Invite-1-Role', 'Reporter');
    $I->click('#Invite-1 button[type="submit"]');
    
    $I->seeTableRowOrder(['User2',
                          'Manager1',
                          'Manager2',
                          'User1']);
  }
}

?>
