<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T052_AccountSettingsSecurity_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User3');
    $I->amOnPage('/account/security');
  }
  
  private function submitPasswordChange(AcceptanceTester $I, string $old_password, string $new_password, string $new_password_repeated): void{
    $I->fillField('#ChangePassword-1-OldPassword', $old_password);
    $I->fillField('#ChangePassword-1-NewPassword', $new_password);
    $I->fillField('#ChangePassword-1-NewPasswordRepeated', $new_password_repeated);
    $I->click('#ChangePassword-1 button[type="submit"]');
  }
  
  public function oldPasswordDoesNotMatch(AcceptanceTester $I): void{
    $this->submitPasswordChange($I, 'invalid-password', '999888777', '999888777');
    $I->seeElement('#ChangePassword-1-OldPassword + .error');
  }
  
  public function newPasswordNotLongEnough(AcceptanceTester $I): void{
    $this->submitPasswordChange($I, '123123123', '123456', '123456');
    $I->seeElement('#ChangePassword-1-NewPassword + .error');
  }
  
  public function newPasswordDoesNotMatch(AcceptanceTester $I): void{
    $this->submitPasswordChange($I, '123123123', '999888777', '777888999');
    $I->seeElement('#ChangePassword-1-NewPasswordRepeated + .error');
  }
  
  public function changePassword(AcceptanceTester $I): void{
    $this->submitPasswordChange($I, '123123123', '999888777', '999888777');
    $I->seeElement('#ChangePassword-1 .success');
    
    $this->submitPasswordChange($I, '999888777', '123123123', '123123123');
    $I->seeElement('#ChangePassword-1 .success');
  }
}

?>
