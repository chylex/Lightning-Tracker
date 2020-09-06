<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T008_ProjectVisibility_Cest{
  private function ensureCanSee(AcceptanceTester $I, array $projects): void{
    $I->amOnPage('/');
    
    foreach($projects as $project){
      $I->seeElement('table a[href="http://localhost/project/'.$project.'"]');
    }
    
    foreach($projects as $project){
      $I->amOnPage('/project/'.$project);
      $I->dontSee('Project Error', 'h2');
    }
  }
  
  private function ensureCannotSee(AcceptanceTester $I, array $projects): void{
    $I->amOnPage('/');
    
    foreach($projects as $project){
      $I->dontSeeElement('table a[href="http://localhost/project/'.$project.'"]');
    }
    
    foreach($projects as $project){
      $I->amOnPage('/project/'.$project);
      $I->see('Project Error', 'h2');
    }
  }
  
  public function canSeeAllProjectsAsAdmin(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    
    $this->ensureCanSee($I, [
        'AdminVisible', 'AdminHidden',
        'User1Visible', 'User1Hidden',
        'User2Visible', 'User2Hidden'
    ]);
  }
  
  public function canSeeAllProjectsAsModerator(AcceptanceTester $I): void{
    $I->amLoggedIn('Moderator');
    
    $this->ensureCanSee($I, [
        'AdminVisible', 'AdminHidden',
        'User1Visible', 'User1Hidden',
        'User2Visible', 'User2Hidden'
    ]);
  }
  
  public function canSeeSomeProjectsAsUser1(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    
    $this->ensureCanSee($I, [
        'AdminVisible',
        'User1Visible', 'User1Hidden',
        'User2Visible'
    ]);
    
    $this->ensureCannotSee($I, [
        'AdminHidden',
        'User2Hidden'
    ]);
  }
  
  public function canSeeSomeProjectsAsUser2(AcceptanceTester $I): void{
    $I->amLoggedIn('User2');
    
    $this->ensureCanSee($I, [
        'AdminVisible',
        'User1Visible',
        'User2Visible', 'User2Hidden'
    ]);
    
    $this->ensureCannotSee($I, [
        'AdminHidden',
        'User1Hidden'
    ]);
  }
}

?>
