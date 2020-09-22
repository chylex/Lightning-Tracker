<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;

class T022_ProjectDeletion_Cest{
  private function ensureCanDelete(AcceptanceTester $I, array $projects): void{
    foreach($projects as $project){
      $I->amOnPage('/project/'.$project.'/delete');
      $I->dontSee('Permission Error', 'h2');
    }
  }
  
  private function ensureCannotDelete(AcceptanceTester $I, array $projects): void{
    foreach($projects as $project){
      $I->amOnPage('/project/'.$project.'/delete');
      $I->see('Permission Error', 'h2');
    }
  }
  
  private function ensureCannotSee(AcceptanceTester $I, array $projects): void{
    foreach($projects as $project){
      $I->amOnPage('/project/'.$project.'/delete');
      $I->see('Project Error', 'h2');
    }
  }
  
  private function submitDeletionOfAdminHiddenProject(AcceptanceTester $I, string $confirmation): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/project/AdminHidden/delete');
    $I->fillField('Name', $confirmation);
    $I->click('button[type="submit"]');
  }
  
  public function nonExistentProject(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $this->ensureCannotSee($I, ['invalid']);
  }
  
  public function canDeleteAllProjectsAsAdmin(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    
    $this->ensureCanDelete($I, [
        'AdminVisible', 'AdminHidden',
        'User1Visible', 'User1Hidden',
        'User2Visible', 'User2Hidden',
    ]);
  }
  
  public function canDeleteAllProjectsAsModerator(AcceptanceTester $I): void{
    $I->amLoggedIn('Moderator');
    
    $this->ensureCanDelete($I, [
        'AdminVisible', 'AdminHidden',
        'User1Visible', 'User1Hidden',
        'User2Visible', 'User2Hidden',
    ]);
  }
  
  public function canDeletePubliclyVisibleProjectsAsManager1(AcceptanceTester $I): void{
    $I->amLoggedIn('Manager1');
    
    $this->ensureCanDelete($I, [
        'AdminVisible',
        'User1Visible',
        'User2Visible',
    ]);
    
    $this->ensureCannotSee($I, [
        'AdminHidden',
        'User1Hidden',
        'User2Hidden',
    ]);
  }
  
  /**
   * @example ["User1"]
   * @example ["User2"]
   */
  public function cannotDeleteOwnedProjects(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn($example[0]);
    
    $this->ensureCannotDelete($I, [
        $example[0].'Visible',
        $example[0].'Hidden',
    ]);
  }
  
  public function confirmationIsEmpty(AcceptanceTester $I): void{
    $this->submitDeletionOfAdminHiddenProject($I, '');
    $I->seeElement('#Confirm-1-Name + .error');
  }
  
  public function confirmationDoesNotMatch(AcceptanceTester $I): void{
    $this->submitDeletionOfAdminHiddenProject($I, 'invalid');
    $I->seeElement('#Confirm-1-Name + .error');
  }
  
  public function confirmationIsCaseSensitive(AcceptanceTester $I): void{
    $this->submitDeletionOfAdminHiddenProject($I, 'adminhidden');
    $I->seeElement('#Confirm-1-Name + .error');
  }
  
  public function deleteProject(AcceptanceTester $I): void{
    $this->submitDeletionOfAdminHiddenProject($I, 'AdminHidden');
    $I->amOnPage('/');
    $I->dontSeeInDatabase('projects', ['url' => 'AdminHidden']);
    
    $I->fillField('#Create-1-Name', 'AdminHidden');
    $I->fillField('#Create-1-Url', 'AdminHidden');
    $I->checkOption('#Create-1-Hidden');
    $I->click('#Create-1 button[type="submit"]');
    $I->seeCurrentUrlEquals('/project/AdminHidden');
  }
}

?>
