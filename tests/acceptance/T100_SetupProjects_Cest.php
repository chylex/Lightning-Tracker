<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T100_SetupProjects_Cest{
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  private function createProject(AcceptanceTester $I, string $name, string $url): void{
    $I->amOnPage('/');
    
    $I->fillField('#Create-1-Name', $name);
    $I->fillField('#Create-1-Url', $url);
    $I->checkOption('#Create-1-Hidden');
    $I->click('#Create-1 button[type="submit"]');
    $I->seeCurrentUrlEquals('/project/'.$url);
  }
  
  public function run(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $this->createProject($I, 'Project 1', 'p1');
    
    $I->amLoggedIn('User2');
    $this->createProject($I, 'Project 2', 'p2');
  }
}

?>
