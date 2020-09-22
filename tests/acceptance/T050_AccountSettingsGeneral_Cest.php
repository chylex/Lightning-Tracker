<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;

class T050_AccountSettingsGeneral_Cest{
  /**
   * @example ["Admin", "admin@example.com"]
   * @example ["Moderator", "moderator@example.com"]
   * @example ["User1", "user1@example.com"]
   * @example ["RoleLess", "role-less@example.com"]
   */
  public function fieldsArePrefilledCorrectly(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn($example[0]);
    $I->amOnPage('/account');
    $I->seeInField('input[name="Name"]', $example[0]);
    $I->seeInField('input[name="Email"]', $example[1]);
  }
}

?>
