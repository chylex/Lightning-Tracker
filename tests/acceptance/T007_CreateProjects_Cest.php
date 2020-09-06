<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T007_CreateProjects_Cest{
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function createProject(AcceptanceTester $I, string $name, bool $hidden): void{
    $I->amOnPage('/');
    
    $I->fillField('#Create-1-Name', $name);
    $I->fillField('#Create-1-Url', $name);
    
    if ($hidden){
      $I->checkOption('#Create-1-Hidden');
    }
    else{
      $I->uncheckOption('#Create-1-Hidden');
    }
    
    $I->click('#Create-1 button[type="submit"]');
    $I->seeCurrentUrlEquals('/project/'.$name);
  }
  
  public function createProjectsAsAdmin(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $this->createProject($I, 'AdminVisible', false);
    $this->createProject($I, 'AdminHidden', true);
  }
  
  public function createProjectsAsUser1(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $this->createProject($I, 'User1Visible', false);
    $this->createProject($I, 'User1Hidden', true);
  }
  
  public function createProjectsAsUser2(AcceptanceTester $I): void{
    $I->amLoggedIn('User2');
    $this->createProject($I, 'User2Visible', false);
    $this->createProject($I, 'User2Hidden', true);
  }
}

?>
