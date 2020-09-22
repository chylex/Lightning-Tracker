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
  
  private function fillWithOverride(AcceptanceTester $I, string $override_field, string $override_value): void{
    $fields = [
        'BaseUrl'               => 'http://localhost',
        'AdminName'             => 'Admin',
        'AdminPassword'         => '123456789',
        'AdminPasswordRepeated' => '123456789',
        'AdminEmail'            => 'admin@example.com',
        'DbName'                => 'tracker_test',
        'DbHost'                => 'localhost',
        'DbUser'                => 'lt',
        'DbPassword'            => 'test',
    ];
    
    foreach($fields as $field => $value){
      $I->fillField($field, $field === $override_field ? $override_value : $value);
    }
  }
  
  private function submitExpectingError(AcceptanceTester $I, string $error): void{
    $I->click('button[value=""]');
    $I->see($error, '.error');
  }
  
  private function reinstall(AcceptanceTester $I, string $conflict_resolution, string $input_email, string $result_email): void{
    $I->assertTrue(unlink(__DIR__.'/../../server/www/config.php'));
    $I->amOnPage('/');
    
    $this->fillWithOverride($I, 'AdminEmail', $input_email);
    $I->click('button[value=""]');
    
    $I->seeElement('#form-install-section[style="display:none"]');
    $I->seeElement('#form-conflict-section:not([style])');
    
    $I->fillField('_Resolution', $conflict_resolution);
    $I->click('button[value="ConflictConfirm"]');
    
    $I->see('Register', 'a[href="http://localhost/register"]');
    $I->seeInDatabase('users', [
        'name'    => 'Admin',
        'email'   => $result_email,
        'role_id' => 1,
    ]);
  }
  
  public function baseUrlMissingProtocol(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'BaseUrl', 'localhost');
    $this->submitExpectingError($I, 'Base URL');
  }
  
  public function baseUrlProhibitedProtocol(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'BaseUrl', 'ftp://localhost');
    $this->submitExpectingError($I, 'Base URL');
  }
  
  public function baseUrlInvalidDomain(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'BaseUrl', 'http://');
    $this->submitExpectingError($I, 'Base URL');
  }
  
  public function databaseNameEmpty(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'DbName', '');
    $this->submitExpectingError($I, 'Database name');
  }
  
  public function databaseHostEmpty(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'DbHost', '');
    $this->submitExpectingError($I, 'Database host');
  }
  
  public function databaseUserEmpty(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'DbUser', '');
    $this->submitExpectingError($I, 'Database user');
  }
  
  public function databasePasswordEmpty(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'DbPassword', '');
    $this->submitExpectingError($I, 'Database password');
  }
  
  public function databaseNameInvalid(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'DbName', 'tracker_test_invalid');
    $this->submitExpectingError($I, 'invalid credentials or database name');
  }
  
  public function databaseCredentialsInvalid(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'DbPassword', '123');
    $this->submitExpectingError($I, 'invalid credentials or database name');
  }
  
  public function adminNameEmpty(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'AdminName', '');
    $this->submitExpectingError($I, 'Administrator account name');
  }
  
  public function adminPasswordNotLongEnough(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'AdminPassword', '123456');
    $I->fillField('AdminPasswordRepeated', '123456');
    $this->submitExpectingError($I, 'Administrator password ');
  }
  
  public function adminPasswordDoesNotMatch(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'AdminPasswordRepeated', '123455789');
    $this->submitExpectingError($I, 'Administrator passwords');
  }
  
  public function adminEmailInvalid(AcceptanceTester $I): void{
    $this->fillWithOverride($I, 'AdminEmail', 'example.com');
    $this->submitExpectingError($I, 'Administrator email');
  }
  
  /**
   * @depends baseUrlMissingProtocol
   * @depends baseUrlProhibitedProtocol
   * @depends baseUrlInvalidDomain
   * @depends databaseNameEmpty
   * @depends databaseHostEmpty
   * @depends databaseUserEmpty
   * @depends databasePasswordEmpty
   * @depends databaseNameInvalid
   * @depends databaseCredentialsInvalid
   * @depends adminNameEmpty
   * @depends adminPasswordNotLongEnough
   * @depends adminPasswordDoesNotMatch
   * @depends adminEmailInvalid
   */
  public function install(AcceptanceTester $I, bool $skipReinstall = false): void{
    $email = $skipReinstall ? 'admin@example.com' : 'firstadmin@example.com';
    
    $this->fillWithOverride($I, 'AdminEmail', $email);
    $I->click('button[value=""]');
    
    $I->see('Register', 'a[href="http://localhost/register"]');
    $I->dontSeeCookie('logon');
    $I->seeInDatabase('users', [
        'name'    => 'Admin',
        'email'   => $email,
        'role_id' => 1,
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
