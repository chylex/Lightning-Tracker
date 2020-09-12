<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;

class T006_PageLoginRedirect_Cest{
  private function ensureCanAccessPageAs(AcceptanceTester $I, ?string $user, string $page): void{
    if ($user === null){
      $I->amNotLoggedIn();
      $I->amOnPage('/'.$page);
      $I->seeCurrentUrlEquals('/'.$page);
    }
    else{
      $I->amLoggedIn($user);
      $I->amOnPage('/'.$page);
    }
    
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function ensureCannotAccessPageAs(AcceptanceTester $I, ?string $user, string $page): void{
    if ($user === null){
      $I->amNotLoggedIn();
      $I->amOnPage('/'.$page);
      $I->seeCurrentUrlEquals('/login?return='.$page);
    }
    else{
      $I->amLoggedIn($user);
      $I->amOnPage('/'.$page);
      $I->see('Permission Error', 'h2');
    }
  }
  
  /**
   * @example [null]
   * @example ["RoleLess"]
   * @example ["User1"]
   * @example ["User2"]
   * @example ["Manager1"]
   * @example ["Manager2"]
   * @example ["Moderator"]
   * @example ["Admin"]
   */
  public function everyoneCanAccessProjects(AcceptanceTester $I, Example $example): void{
    $this->ensureCanAccessPageAs($I, $example[0], '');
  }
  
  /**
   * @example [null]
   * @example ["RoleLess"]
   * @example ["User1"]
   * @example ["User2"]
   * @example ["Manager1"]
   * @example ["Manager2"]
   * @example ["Moderator"]
   * @example ["Admin"]
   */
  public function everyoneCanAccessAbout(AcceptanceTester $I, Example $example): void{
    $this->ensureCanAccessPageAs($I, $example[0], 'about');
  }
  
  /**
   * @example ["RoleLess"]
   * @example ["User1"]
   * @example ["User2"]
   * @example ["Manager1"]
   * @example ["Manager2"]
   * @example ["Moderator"]
   * @example ["Admin"]
   */
  public function loggedInCanAccessAccount(AcceptanceTester $I, Example $example): void{
    $this->ensureCanAccessPageAs($I, $example[0], 'account');
  }
  
  /**
   * @example [null]
   */
  public function loggedOutCannotAccessAccount(AcceptanceTester $I, Example $example): void{
    $this->ensureCannotAccessPageAs($I, $example[0], 'account');
  }
  
  /**
   * @example ["Manager1"]
   * @example ["Manager2"]
   * @example ["Moderator"]
   * @example ["Admin"]
   */
  public function privilegedCanAccessUsers(AcceptanceTester $I, Example $example): void{
    $this->ensureCanAccessPageAs($I, $example[0], 'users');
  }
  
  /**
   * @example [null]
   * @example ["RoleLess"]
   * @example ["User1"]
   * @example ["User2"]
   */
  public function notPrivilegedCannotAccessUsers(AcceptanceTester $I, Example $example): void{
    $this->ensureCannotAccessPageAs($I, $example[0], 'users');
  }
  
  /**
   * @example ["Manager1"]
   * @example ["Admin"]
   */
  public function privilegedCanAccessSettings(AcceptanceTester $I, Example $example): void{
    $this->ensureCanAccessPageAs($I, $example[0], 'settings');
  }
  
  /**
   * @example [null]
   * @example ["RoleLess"]
   * @example ["User1"]
   * @example ["User2"]
   * @example ["Manager2"]
   * @example ["Moderator"]
   */
  public function notPrivilegedCannotAccessSettings(AcceptanceTester $I, Example $example): void{
    $this->ensureCannotAccessPageAs($I, $example[0], 'settings');
  }
}

?>
