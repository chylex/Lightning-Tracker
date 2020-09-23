<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Codeception\Example;
use Helper\Acceptance;

class T135_MilestoneActivation_Cest{
  /** @noinspection SqlWithoutWhere */
  public function _after(): void{
    Acceptance::getDB()->exec('UPDATE project_user_settings SET active_milestone = NULL');
  }
  
  private function assertProgressBarMatches(AcceptanceTester $I, string $selector, string $value): void{
    $I->see($value, $selector.' .progress-bar');
    $I->assertEquals($value, $I->grabAttributeFrom($selector.' .progress-bar .value', 'data-value'));
  }
  
  /**
   * @example ["Admin"]
   * @example ["Moderator"]
   * @example ["Manager1"]
   * @example ["Manager2"]
   * @example ["User1"]
   * @example ["User2"]
   */
  public function allInactiveByDefault(AcceptanceTester $I, Example $example): void{
    $I->amLoggedIn($example[0]);
    
    foreach(['p1', 'p2'] as $project){
      $I->amOnPage('/project/'.$project.'/milestones');
      $I->seeElement('span.icon.icon-radio-unchecked');
      $I->dontSeeElement('span.icon.icon-radio-checked');
    }
  }
  
  public function toggleOneMilestone(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/milestones');
    
    $I->seeElement("#ToggleActive-2 span.icon.icon-radio-unchecked");
    $I->click('#ToggleActive-2 button[type="submit"]');
    $I->seeElement("#ToggleActive-2 span.icon.icon-radio-checked");
    $I->click('#ToggleActive-2 button[type="submit"]');
    $I->seeElement("#ToggleActive-2 span.icon.icon-radio-unchecked");
  }
  
  public function toggleBetweenMilestones(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/milestones');
    
    $I->seeElement("#ToggleActive-1 span.icon.icon-radio-unchecked");
    $I->click('#ToggleActive-1 button[type="submit"]');
    $I->seeElement("#ToggleActive-1 span.icon.icon-radio-checked");
    
    $I->seeElement("#ToggleActive-2 span.icon.icon-radio-unchecked");
    $I->click('#ToggleActive-2 button[type="submit"]');
    $I->seeElement("#ToggleActive-2 span.icon.icon-radio-checked");
    $I->seeElement("#ToggleActive-1 span.icon.icon-radio-unchecked");
    
    $I->seeElement("#ToggleActive-3 span.icon.icon-radio-unchecked");
    $I->click('#ToggleActive-3 button[type="submit"]');
    $I->seeElement("#ToggleActive-3 span.icon.icon-radio-checked");
    $I->seeElement("#ToggleActive-2 span.icon.icon-radio-unchecked");
    $I->seeElement("#ToggleActive-1 span.icon.icon-radio-unchecked");
  }
  
  public function eachUserHasOwnActiveMilestone(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/project/p1/milestones');
    $I->click('#ToggleActive-2 button[type="submit"]');
    $I->amOnPage('/project/p2/milestones');
    $I->click('#ToggleActive-1 button[type="submit"]');
    
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/milestones');
    $I->click('#ToggleActive-1 button[type="submit"]');
    $I->amOnPage('/project/p2/milestones');
    $I->click('#ToggleActive-1 button[type="submit"]');
    
    $I->amLoggedIn('User2');
    $I->amOnPage('/project/p1/milestones');
    $I->click('#ToggleActive-1 button[type="submit"]');
    
    $I->amLoggedIn('Admin');
    $I->amOnPage('/project/p1/milestones');
    $I->seeElement("#ToggleActive-2 span.icon.icon-radio-checked");
    $I->amOnPage('/project/p2/milestones');
    $I->seeElement("#ToggleActive-1 span.icon.icon-radio-checked");
    
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/milestones');
    $I->seeElement("#ToggleActive-1 span.icon.icon-radio-checked");
    $I->amOnPage('/project/p2/milestones');
    $I->seeElement("#ToggleActive-1 span.icon.icon-radio-checked");
    
    $I->amLoggedIn('User2');
    $I->amOnPage('/project/p1/milestones');
    $I->seeElement("#ToggleActive-1 span.icon.icon-radio-checked");
    $I->amOnPage('/project/p2/milestones');
    $I->dontSeeElement("span.icon.icon-radio-checked");
  }
  
  public function activeMilestoneWidgetMatchesInProject1(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/project/p1/milestones');
    $this->assertProgressBarMatches($I, 'tbody tr:first-child', '62');
    $I->click('#ToggleActive-1 button[type="submit"]');
    
    $I->amOnPage('/project/p1/issues');
    $I->seeElement('#active-milestone');
    $I->see('Milestone', '#active-milestone h4');
    $this->assertProgressBarMatches($I, '#active-milestone', '62');
  }
  
  public function activeMilestoneWidgetMatchesInProject2(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/project/p2/milestones');
    $this->assertProgressBarMatches($I, 'tbody tr:first-child', '50');
    $I->click('#ToggleActive-1 button[type="submit"]');
    
    $I->amOnPage('/project/p2/issues');
    $I->seeElement('#active-milestone');
    $I->see('Milestone', '#active-milestone h4');
    $this->assertProgressBarMatches($I, '#active-milestone', '50');
  }
  
  /**
   * @depends activeMilestoneWidgetMatchesInProject1
   * @depends activeMilestoneWidgetMatchesInProject2
   */
  public function activeMilestoneWidgetsMatchInBothProjects(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/project/p1/milestones');
    $I->click('#ToggleActive-1 button[type="submit"]');
    $I->amOnPage('/project/p2/milestones');
    $I->click('#ToggleActive-1 button[type="submit"]');
    
    $I->amOnPage('/project/p1/issues');
    $this->assertProgressBarMatches($I, '#active-milestone', '62');
    $I->amOnPage('/project/p2/issues');
    $this->assertProgressBarMatches($I, '#active-milestone', '50');
  }
}

?>
