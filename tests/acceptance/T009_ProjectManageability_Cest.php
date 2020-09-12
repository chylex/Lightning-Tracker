<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T009_ProjectManageability_Cest{
  private function ensureCanManage(AcceptanceTester $I, array $projects): void{
    $I->amOnPage('/');
    
    foreach($projects as $project){
      $I->amOnPage('/project/'.$project);
      $I->seeElement('#navigation a[href="http://localhost/project/'.$project.'/settings"]');
    }
  }
  
  private function ensureCannotManage(AcceptanceTester $I, array $projects): void{
    $I->amOnPage('/');
    
    foreach($projects as $project){
      $I->amOnPage('/project/'.$project);
      $I->seeElement('#navigation a[href="http://localhost/project/'.$project.'/issues"]');
      $I->dontSeeElement('#navigation a[href="http://localhost/project/'.$project.'/settings"]');
    }
  }
  
  public function canManageAllProjectsAsAdmin(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    
    $this->ensureCanManage($I, [
        'AdminVisible', 'AdminHidden',
        'User1Visible', 'User1Hidden',
        'User2Visible', 'User2Hidden',
    ]);
  }
  
  public function canManageAllProjectsAsModerator(AcceptanceTester $I): void{
    $I->amLoggedIn('Moderator');
    
    $this->ensureCanManage($I, [
        'AdminVisible', 'AdminHidden',
        'User1Visible', 'User1Hidden',
        'User2Visible', 'User2Hidden',
    ]);
  }
  
  public function canManageSomeProjectsAsUser1(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    
    $this->ensureCanManage($I, [
        'User1Visible', 'User1Hidden',
    ]);
    
    $this->ensureCannotManage($I, [
        'AdminVisible',
        'User2Visible',
    ]);
  }
  
  public function canManageSomeProjectsAsUser2(AcceptanceTester $I): void{
    $I->amLoggedIn('User2');
    
    $this->ensureCanManage($I, [
        'User2Visible', 'User2Hidden',
    ]);
    
    $this->ensureCannotManage($I, [
        'AdminVisible',
        'User1Visible',
    ]);
  }
}

?>
