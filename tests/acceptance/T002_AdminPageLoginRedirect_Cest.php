<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T002_AdminPageLoginRedirect_Cest{
  public function canAccessAboutPage(AcceptanceTester $I): void{
    $I->amOnPage('/about');
    $I->seeCurrentUrlEquals('/about');
  }
  
  public function cannotAccessUsersPage(AcceptanceTester $I): void{
    $I->amOnPage('/users');
    $I->seeCurrentUrlEquals('/login?return=users');
  }
  
  public function cannotAccessSettingsPage(AcceptanceTester $I): void{
    $I->amOnPage('/settings');
    $I->seeCurrentUrlEquals('/login?return=settings');
  }
}

?>
