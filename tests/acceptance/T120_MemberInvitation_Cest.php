<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T120_MemberInvitation_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/members');
  }
  
  private function inviteUser(AcceptanceTester $I, string $name, string $role): void{
    $I->fillField('#Invite-1-Name', $name);
    $I->selectOption('#Invite-1-Role', $role);
    $I->click('#Invite-1 button[type="submit"]');
  }
  
  public function userDoesNotExist(AcceptanceTester $I): void{
    $this->inviteUser($I, 'InvalidUser', '(Default)');
    $I->see('not found', '#Invite-1-Name + .error');
  }
  
  public function userIsTheOwner(AcceptanceTester $I): void{
    $this->inviteUser($I, 'User1', '(Default)');
    $I->see('is the owner', '#Invite-1-Name + .error');
  }
  
  public function userIsAlreadyAMember(AcceptanceTester $I): void{
    $this->inviteUser($I, 'User2', '(Default)');
    $I->see('is already a member', '#Invite-1-Name + .error');
  }
}

?>
