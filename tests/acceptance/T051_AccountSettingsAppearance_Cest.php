<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T051_AccountSettingsAppearance_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User3');
    $I->amOnPage('/account/appearance');
  }
  
  public function fieldsArePrefilledCorrectly(AcceptanceTester $I): void{
    $I->seeInField('#ChangeAppearance-1-TablePaginationElements', 15);
  }
  
  public function tablePaginationTooLow(AcceptanceTester $I): void{
    $I->fillField('#ChangeAppearance-1-TablePaginationElements', 4);
    $I->click('button[type="submit"]');
    $I->seeElement('#ChangeAppearance-1-TablePaginationElements + .error');
    $I->seeInField('#ChangeAppearance-1-TablePaginationElements', 5);
  }
  
  public function tablePaginationTooHigh(AcceptanceTester $I): void{
    $I->fillField('#ChangeAppearance-1-TablePaginationElements', 51);
    $I->click('button[type="submit"]');
    $I->seeElement('#ChangeAppearance-1-TablePaginationElements + .error');
    $I->seeInField('#ChangeAppearance-1-TablePaginationElements', 50);
  }
}

?>
