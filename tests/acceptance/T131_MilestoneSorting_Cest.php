<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T131_MilestoneSorting_Cest{
  private function viewProject(AcceptanceTester $I, int $project): void{
    $I->amLoggedIn('User'.$project);
    $I->amOnPage('/project/p'.$project.'/milestones');
  }
  
  public function sortByNameInProject1(AcceptanceTester $I): void{
    $order = [
        'Milestone',
        'Milestone 2',
        'Milestone 3',
        'Milestone 4',
    ];
    
    $this->viewProject($I, 1);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
  
  public function sortByNameInProject2(AcceptanceTester $I): void{
    $order = [
        'Fourth Milestone',
        'Milestone',
        'Second Milestone',
        'Third Milestone',
    ];
    
    $this->viewProject($I, 2);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(1) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
  
  public function sortByProgressInProject1(AcceptanceTester $I): void{
    $order = [
        'Milestone 3',
        'Milestone 4',
        'Milestone',
        'Milestone 2',
    ];
    
    $this->viewProject($I, 1);
    
    $I->click('thead tr:first-child th:nth-child(4) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(4) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
  
  public function sortByLastUpdatedInProject1(AcceptanceTester $I): void{
    $order = [
        'Milestone 4',
        'Milestone 3',
        'Milestone 2',
        'Milestone',
    ];
    
    $this->viewProject($I, 1);
    
    $I->click('thead tr:first-child th:nth-child(5) > a');
    $I->seeTableRowOrder($order);
    
    $I->click('thead tr:first-child th:nth-child(5) > a');
    $I->seeTableRowOrder(array_reverse($order));
  }
}

?>
