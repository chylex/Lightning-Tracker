<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T001_Install_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amOnPage('/');
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function fill(AcceptanceTester $I, string $email): void{
    $I->fillField('BaseUrl', 'http://localhost');
    $I->fillField('AdminName', 'Admin');
    $I->fillField('AdminPassword', '123456789');
    $I->fillField('AdminPasswordRepeated', '123456789');
    $I->fillField('AdminEmail', $email);
    $I->fillField('DbName', 'tracker_test');
    $I->fillField('DbHost', 'localhost');
    $I->fillField('DbUser', 'lt');
    $I->fillField('DbPassword', 'test');
  }
  
  private function reinstall(AcceptanceTester $I, string $conflict_resolution, string $input_email, string $result_email): void{
    $I->assertTrue(unlink(__DIR__.'/../../server/www/config.php'));
    $I->amOnPage('/');
    
    $this->fill($I, $input_email);
    $I->click('button[value=""]');
    
    $I->seeElement('#form-install-section[style="display:none"]');
    $I->seeElement('#form-conflict-section:not([style])');
    
    $I->fillField('_Resolution', $conflict_resolution);
    $I->click('button[value="ConflictConfirm"]');
    
    $I->see('Register', 'a[href="http://localhost/register"]');
    $I->seeInDatabase('users', [
        'name'  => 'Admin',
        'email' => $result_email,
        'admin' => true
    ]);
  }
  
  public function install(AcceptanceTester $I, bool $skipReinstall = false): void{
    $email = $skipReinstall ? 'admin@example.com' : 'firstadmin@example.com';
    
    $this->fill($I, $email);
    $I->click('button[value=""]');
    
    $I->see('Register', 'a[href="http://localhost/register"]');
    $I->dontSeeCookie('logon');
    $I->seeInDatabase('users', [
        'name'  => 'Admin',
        'email' => $email,
        'admin' => true
    ]);
  }
  
  /**
   * @depends install
   */
  public function reinstallReuse(AcceptanceTester $I): void{
    $this->reinstall($I, 'reuse', 'secondadmin@example.com', 'firstadmin@example.com');
  }
  
  /**
   * @depends reinstallReuse
   */
  public function reinstallDelete(AcceptanceTester $I): void{
    $this->reinstall($I, 'delete', 'admin@example.com', 'admin@example.com');
  }
}
