<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;

class T010_PagePermissions_Cest{
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
      $I->seeCurrentUrlEquals('/login?return='.rawurlencode($page));
    }
    else{
      $I->amLoggedIn($user);
      $I->amOnPage('/'.$page);
      $I->see('Permission Error', 'h2');
    }
  }
  
  private function ensureCannotAccessProjectPageAs(AcceptanceTester $I, ?string $user, string $project, string $page): void{
    $base = '/project/'.$project;
    
    if ($user === null){
      $I->amNotLoggedIn();
      $I->amOnPage($base.'/'.$page);
      $I->seeCurrentUrlEquals($base.'/login?return='.rawurlencode($page));
    }
    else{
      $I->amLoggedIn($user);
      $I->amOnPage($base.'/'.$page);
      $I->see('Permission Error', 'h2');
    }
  }
  
  private function ensureCanAccessPublicPages(AcceptanceTester $I, string $user): void{
    $this->ensureCanAccessPageAs($I, $user, '');
    $this->ensureCanAccessPageAs($I, $user, 'about');
    $this->ensureCanAccessPageAs($I, $user, 'login');
    $this->ensureCanAccessPageAs($I, $user, 'register');
    $this->ensureCanAccessPageAs($I, $user, 'account');
  }
  
  public function checkLoggedOut(AcceptanceTester $I): void{
    $this->ensureCanAccessPageAs($I, null, '');
    $this->ensureCanAccessPageAs($I, null, 'about');
    $this->ensureCanAccessPageAs($I, null, 'login');
    $this->ensureCanAccessPageAs($I, null, 'register');
    
    $this->ensureCannotAccessProjectPageAs($I, null, 'AdminVisible', 'delete');
    $this->ensureCannotAccessPageAs($I, null, 'users');
    $this->ensureCannotAccessPageAs($I, null, 'users/000000000');
    $this->ensureCannotAccessPageAs($I, null, 'users/000000000/delete');
    $this->ensureCannotAccessPageAs($I, null, 'settings');
    $this->ensureCannotAccessPageAs($I, null, 'settings/roles');
    $this->ensureCannotAccessPageAs($I, null, 'settings/roles/1');
    $this->ensureCannotAccessPageAs($I, null, 'account');
  }
  
  /**
   * @example ["RoleLess"]
   * @example ["User1"]
   * @example ["User2"]
   */
  public function checkUnprivileged(AcceptanceTester $I, Example $example): void{
    $user = $example[0];
    $this->ensureCanAccessPublicPages($I, $user);
    
    $this->ensureCannotAccessProjectPageAs($I, $user, 'AdminVisible', 'delete');
    $this->ensureCannotAccessPageAs($I, $user, 'users');
    $this->ensureCannotAccessPageAs($I, $user, 'users/000000000');
    $this->ensureCannotAccessPageAs($I, $user, 'users/000000000/delete');
    $this->ensureCannotAccessPageAs($I, $user, 'settings');
    $this->ensureCannotAccessPageAs($I, $user, 'settings/roles');
    $this->ensureCannotAccessPageAs($I, $user, 'settings/roles/1');
  }
  
  /**
   * @example ["Manager2"]
   */
  public function checkPartiallyPrivilegedNoProjectDeletionOrSettings(AcceptanceTester $I, Example $example): void{
    $user = $example[0];
    $this->ensureCanAccessPublicPages($I, $user);
    
    $this->ensureCanAccessPageAs($I, $user, 'users');
    $this->ensureCanAccessPageAs($I, $user, 'users/000000000');
    $this->ensureCanAccessPageAs($I, $user, 'users/000000000/delete');
    
    $this->ensureCannotAccessProjectPageAs($I, $user, 'AdminVisible', 'delete');
    $this->ensureCannotAccessPageAs($I, $user, 'settings');
    $this->ensureCannotAccessPageAs($I, $user, 'settings/roles');
    $this->ensureCannotAccessPageAs($I, $user, 'settings/roles/1');
  }
  
  /**
   * @example ["Manager1"]
   */
  public function checkPartiallyPrivilegedNoHiddenProjectDeletion(AcceptanceTester $I, Example $example): void{
    $user = $example[0];
    $this->ensureCanAccessPublicPages($I, $user);
    
    $this->ensureCanAccessPageAs($I, $user, 'project/AdminVisible/delete');
    $this->ensureCanAccessPageAs($I, $user, 'users');
    $this->ensureCanAccessPageAs($I, $user, 'users/000000000');
    $this->ensureCanAccessPageAs($I, $user, 'users/000000000/delete');
    $this->ensureCanAccessPageAs($I, $user, 'settings');
    $this->ensureCanAccessPageAs($I, $user, 'settings/roles');
    $this->ensureCanAccessPageAs($I, $user, 'settings/roles/1');
  }
  
  /**
   * @example ["Moderator"]
   */
  public function checkPartiallyPrivilegedNoSettings(AcceptanceTester $I, Example $example): void{
    $user = $example[0];
    $this->ensureCanAccessPublicPages($I, $user);
    
    $this->ensureCanAccessPageAs($I, $user, 'project/AdminVisible/delete');
    $this->ensureCanAccessPageAs($I, $user, 'project/AdminHidden/delete');
    $this->ensureCanAccessPageAs($I, $user, 'users');
    $this->ensureCanAccessPageAs($I, $user, 'users/000000000');
    $this->ensureCanAccessPageAs($I, $user, 'users/000000000/delete');
    
    $this->ensureCannotAccessPageAs($I, $user, 'settings');
    $this->ensureCannotAccessPageAs($I, $user, 'settings/roles');
    $this->ensureCannotAccessPageAs($I, $user, 'settings/roles/1');
  }
  
  /**
   * @example ["Admin"]
   */
  public function checkFullyPrivileged(AcceptanceTester $I, Example $example): void{
    $user = $example[0];
    $this->ensureCanAccessPublicPages($I, $user);
    
    $this->ensureCanAccessPageAs($I, $user, 'project/AdminVisible/delete');
    $this->ensureCanAccessPageAs($I, $user, 'project/AdminHidden/delete');
    $this->ensureCanAccessPageAs($I, $user, 'users');
    $this->ensureCanAccessPageAs($I, $user, 'users/000000000');
    $this->ensureCanAccessPageAs($I, $user, 'users/000000000/delete');
    $this->ensureCanAccessPageAs($I, $user, 'settings');
    $this->ensureCanAccessPageAs($I, $user, 'settings/roles');
    $this->ensureCanAccessPageAs($I, $user, 'settings/roles/1');
  }
}

?>
