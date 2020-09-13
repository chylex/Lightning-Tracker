<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;

class T040_SystemSettingsGeneral_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/settings');
  }
  
  public function checkNoBackupFile(AcceptanceTester $I): void{
    $I->dontSeeElement('button[value="RemoveBackup"]');
  }
  
  /**
   * @depends checkNoBackupFile
   */
  public function disableRegistrations(AcceptanceTester $I): void{
    $I->uncheckOption('SysEnableRegistration');
    $I->click('button[value="UpdateSettings"]');
    $I->seeElement('form .success');
    
    $I->amNotLoggedIn();
    $I->amOnPage('/');
    $I->see('Login', 'a[href="http://localhost/login"]');
    $I->dontSee('Register', 'a[href="http://localhost/register"]');
  }
  
  /**
   * @depends checkNoBackupFile
   * @example [0]
   * @example [1]
   * @example [2]
   */
  public function changeBaseUrlWithTrailingSlashes(AcceptanceTester $I, Example $example): void{
    $I->fillField('BaseUrl', 'http://localhost/tracker'.str_repeat('/', $example[0]));
    $I->click('button[value="UpdateSettings"]');
    $I->seeElement('form .success');
    $I->seeElement('#navigation a[href="http://localhost/tracker/settings"]');
  }
  
  /**
   * @depends changeBaseUrlWithTrailingSlashes
   */
  public function undoBaseUrl(AcceptanceTester $I): void{
    $I->fillField('BaseUrl', 'http://localhost');
    $I->click('button[value="UpdateSettings"]');
    $I->seeElement('form .success');
    $I->seeElement('#navigation a[href="http://localhost/settings"]');
  }
  
  /**
   * @depends disableRegistrations
   * @depends undoBaseUrl
   */
  public function removeBackupFile(AcceptanceTester $I): void{
    $I->click('button[value="RemoveBackup"]');
    $I->seeElement('form .success');
    $this->checkNoBackupFile($I);
  }
}

?>
