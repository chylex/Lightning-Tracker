<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T150_ProjectSettingsGeneral_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/settings');
  }
  
  public function nameIsEmpty(AcceptanceTester $I): void{
    $I->fillField('#Update-1-Name', '');
    $I->click('button[type="submit"]');
    $I->seeElement('#Update-1-Name + .error');
  }
  
  public function changeName(AcceptanceTester $I): void{
    $I->see('Project 1', 'h1');
    
    $I->fillField('#Update-1-Name', 'Renamed');
    $I->click('button[type="submit"]');
    $I->see('Renamed', 'h1');
    
    $I->fillField('#Update-1-Name', 'Project 1');
    $I->click('button[type="submit"]');
    $I->see('Project 1', 'h1');
  }
  
  public function urlFieldIsDisabledAndIgnored(AcceptanceTester $I): void{
    $I->assertEquals('disabled', $I->grabAttributeFrom('#Update-1-Url', 'disabled'));
    $I->fillField('#Update-1-Url', 'nope');
    $I->click('button[type="submit"]');
    $I->seeInField('#Update-1-Url', 'p1');
  }
  
  public function unhideProject(AcceptanceTester $I): void{
    $I->seeCheckboxIsChecked('#Update-1-Hidden');
    $I->uncheckOption('#Update-1-Hidden');
    $I->click('button[type="submit"]');
    
    $I->amNotLoggedIn();
    $I->amOnPage('/project/p1');
    $I->dontSee('Project Error', 'h2');
  }
  
  /**
   * @depends unhideProject
   */
  public function hideProject(AcceptanceTester $I): void{
    $I->dontSeeCheckboxIsChecked('#Update-1-Hidden');
    $I->checkOption('#Update-1-Hidden');
    $I->click('button[type="submit"]');
    
    $I->amNotLoggedIn();
    $I->amOnPage('/project/p1');
    $I->see('Project Error', 'h2');
  }
}

?>
