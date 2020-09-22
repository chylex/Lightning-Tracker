<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T151_ProjectSettingsDescription_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
  }
  
  public function changeDescription(AcceptanceTester $I): void{
    $I->amOnPage('/project/p1/settings/description');
    $I->fillField('Description', "# Test Description\nParagraph of description.");
    $I->click('button[type="submit"]');
    $I->seeInField('Description', "# Test Description\nParagraph of description.");
  }
  
  /**
   * @depends changeDescription
   */
  public function checkUpdatedDescriptionInDashboard(AcceptanceTester $I): void{
    $I->amOnPage('/project/p1');
    $I->see('Test Description', 'h4');
    $I->see('Paragraph of description.', 'p');
  }
  
  /**
   * @depends checkUpdatedDescriptionInDashboard
   */
  public function revertDescription(AcceptanceTester $I): void{
    $I->amOnPage('/project/p1/settings/description');
    $I->fillField('Description', '');
    $I->click('button[type="submit"]');
    $I->seeInField('Description', '');
  }
}

?>
