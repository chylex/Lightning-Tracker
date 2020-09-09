<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T007_CreateProjects_Cest{
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function createProject(AcceptanceTester $I, bool $hidden, string $name, ?string $url = null): void{
    $url ??= $name;
    
    $I->amOnPage('/');
    
    $I->fillField('#Create-1-Name', $name);
    $I->fillField('#Create-1-Url', $url);
    
    if ($hidden){
      $I->checkOption('#Create-1-Hidden');
    }
    else{
      $I->uncheckOption('#Create-1-Hidden');
    }
    
    $I->click('#Create-1 button[type="submit"]');
    $I->seeCurrentUrlEquals('/project/'.$url);
  }
  
  public function createProjectsAsAdmin(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $this->createProject($I, false, 'AdminVisible');
    $this->createProject($I, true, 'AdminHidden');
  }
  
  public function createProjectsAsUser1(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $this->createProject($I, false, 'User1Visible');
    $this->createProject($I, true, 'User1Hidden');
  }
  
  public function createProjectsAsUser2(AcceptanceTester $I): void{
    $I->amLoggedIn('User2');
    $this->createProject($I, false, 'User2Visible');
    $this->createProject($I, true, 'User2Hidden');
  }
  
  /**
   * @depends createProjectsAsAdmin
   * @depends createProjectsAsUser1
   * @depends createProjectsAsUser2
   */
  public function duplicateNamesWork(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $this->createProject($I, false, 'User1Visible', 'DifferentUrl');
    Acceptance::getDB()->exec('DELETE FROM projects WHERE url = \'DifferentUrl\'');
  }
  
  /**
   * @depends createProjectsAsAdmin
   * @depends createProjectsAsUser1
   * @depends createProjectsAsUser2
   */
  public function urlAlreadyExists(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/');
    
    $urls = ['AdminVisible', 'AdminHidden',
             'User1Visible', 'User1Hidden',
             'User2Visible', 'User2Hidden'];
    
    foreach($urls as $url){
      $I->fillField('#Create-1-Name', 'NewProject');
      $I->fillField('#Create-1-Url', $url);
      $I->click('#Create-1 button[type="submit"]');
      $I->seeElement('#Create-1-Url + .error');
    }
  }
  
  /**
   * @depends urlAlreadyExists
   */
  public function urlIsCaseInsensitive(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/');
    
    $urls = ['adminvisible', 'adminhidden',
             'user1visible', 'user1hidden',
             'user2visible', 'user2hidden'];
    
    foreach($urls as $url){
      $I->fillField('#Create-1-Name', 'NewProject');
      $I->fillField('#Create-1-Url', $url);
      $I->click('#Create-1 button[type="submit"]');
      $I->seeElement('#Create-1-Url + .error');
    }
  }
}

?>
