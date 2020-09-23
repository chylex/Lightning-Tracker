<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;

class T140_IssueCreation_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
  }
  
  /**
   * @example [null, null]
   * @example ["feature", "Feature"]
   * @example ["enhancement", "Enhancement"]
   * @example ["bug", "Bug"]
   * @example ["crash", "Crash"]
   * @example ["task", "Task"]
   */
  public function fieldsArePrefilledCorrectly(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues/new'.($example[0] === null ? '' : '/'.$example[0]));
    
    if ($example[1] !== null){
      $I->seeOptionIsSelected('Type', $example[1]);
    }
    
    $I->seeOptionIsSelected('Priority', 'Medium');
    $I->seeOptionIsSelected('Scale', 'Medium');
    $I->seeOptionIsSelected('Status', 'Open');
    $I->seeInField('Progress', '0');
    $I->seeOptionIsSelected('Milestone', '(None)');
    $I->seeOptionIsSelected('Assignee', '(None)');
  }
  
  public function titleIsEmpty(AcceptanceTester $I): void{
    $I->amOnPage('/project/p1/issues/new/feature');
    $I->fillField('Title', '');
    $I->click('button[type="submit"]');
    $I->seeElement('#Confirm-1-Title + .error');
  }
}

?>
