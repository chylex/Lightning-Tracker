<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;

class T011_PageRouting_Cest{
  /**
   * @example ["/invalid"]
   * @example ["/about/invalid"]
   * @example ["/project"]
   * @example ["/project/AdminVisible/invalid"]
   */
  public function nonExistentPage(AcceptanceTester $I, Example $example): void{
    $I->amOnPage($example[0]);
    $I->seePageNotFound();
    $I->see('Not Found', 'h2');
  }
  
  public function faviconIcoYieldsEmpty404Page(AcceptanceTester $I): void{
    $I->amOnPage('/favicon.ico');
    $I->seePageNotFound();
    $I->assertEmpty($I->grabPageSource());
  }
}

?>
