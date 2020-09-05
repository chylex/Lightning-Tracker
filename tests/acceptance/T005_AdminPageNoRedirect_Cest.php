<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T005_AdminPageNoRedirect_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
  }
  
  public function canAccessUsersPage(AcceptanceTester $I): void{
    $I->amOnPage('/users');
    $I->seeCurrentUrlEquals('/users');
  }
  
  public function canAccessSettingsPage(AcceptanceTester $I): void{
    $I->amOnPage('/settings');
    $I->seeCurrentUrlEquals('/settings');
  }
}

?>
