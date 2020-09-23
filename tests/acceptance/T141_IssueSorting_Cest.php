<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T141_IssueSorting_Cest{
  public function _before(AcceptanceTester $I): void{
    $I->amLoggedIn('User1');
    $I->amOnPage('/project/p1/issues');
  }
  
  public function sortByIdDescIsDefaultInProject1(AcceptanceTester $I): void{
    $I->seeTableRowOrder(['Assigned Test Issue 8 (Feature)',
                          'Assigned Test Issue 7 (Feature)',
                          'Assigned Test Issue 6 (Feature)',
                          'Assigned Test Issue 5 (Crash)',
                          'Assigned Test Issue 4 (Task)',
                          'Assigned Test Issue 3 (Task)',
                          'Assigned Test Issue 2 (Task)',
                          'Assigned Test Issue 1 (Task)']);
  }
  
  public function sortByIdInProject1(AcceptanceTester $I): void{
    $I->click('thead tr:first-child th:nth-child(2) > a');
    
    $I->seeTableRowOrder(['Status Test Issue 1 (Feature)',
                          'Status Test Issue 2 (Feature)',
                          'Status Test Issue 3 (Feature)',
                          'Status Test Issue 4 (Feature)',
                          'Status Test Issue 5 (Feature)',
                          'Status Test Issue 6 (Feature)',
                          'Status Test Issue 7 (Feature)',
                          'Status Test Issue 8 (Feature)',
                          'Status Test Issue 9 (Feature)',
                          'Status Test Issue 10 (Feature)',
                          'Status Test Issue 11 (Feature)',
                          'Status Test Issue 12 (Feature)',
                          'Status Test Issue 13 (Feature)',
                          'Status Test Issue 14 (Feature)']);
    
    $I->click('thead tr:first-child th:nth-child(2) > a');
    
    $I->seeTableRowOrder(['Assigned Test Issue 8 (Feature)',
                          'Assigned Test Issue 7 (Feature)',
                          'Assigned Test Issue 6 (Feature)',
                          'Assigned Test Issue 5 (Crash)',
                          'Assigned Test Issue 4 (Task)',
                          'Assigned Test Issue 3 (Task)',
                          'Assigned Test Issue 2 (Task)',
                          'Assigned Test Issue 1 (Task)']);
  }
  
  public function sortByNameInProject1(AcceptanceTester $I): void{
    $I->click('thead tr:first-child th:nth-child(3) > a');
    
    $I->seeTableRowOrder(['Assigned Test Issue 1 (Task)',
                          'Assigned Test Issue 2 (Task)',
                          'Assigned Test Issue 3 (Task)',
                          'Assigned Test Issue 4 (Task)',
                          'Assigned Test Issue 5 (Crash)',
                          'Assigned Test Issue 6 (Feature)',
                          'Assigned Test Issue 7 (Feature)',
                          'Assigned Test Issue 8 (Feature)',
                          'Milestone 1 Test Issue 1 (Feature)',
                          'Milestone 1 Test Issue 2 (Bug)',
                          'Milestone 2 Test Issue 1 (Enhancement)',
                          'Milestone 2 Test Issue 2 (Enhancement)',
                          'Milestone 2 Test Issue 3 (Enhancement)']);
    
    $I->click('thead tr:first-child th:nth-child(3) > a');
    
    $I->seeTableRowOrder(['Status Test Issue 9 (Feature)',
                          'Status Test Issue 8 (Feature)',
                          'Status Test Issue 7 (Feature)',
                          'Status Test Issue 6 (Feature)',
                          'Status Test Issue 5 (Feature)',
                          'Status Test Issue 4 (Feature)',
                          'Status Test Issue 3 (Feature)',
                          'Status Test Issue 2 (Feature)',
                          'Status Test Issue 14 (Feature)',
                          'Status Test Issue 13 (Feature)',
                          'Status Test Issue 12 (Feature)',
                          'Status Test Issue 11 (Feature)',
                          'Status Test Issue 10 (Feature)',
                          'Status Test Issue 1 (Feature)']);
  }
  
  public function sortByMultipleStatusFieldsInProject1(AcceptanceTester $I): void{
    $I->click('thead tr:first-child th:nth-child(4) > a');
    $I->click('thead tr:first-child th:nth-child(5) > a');
    $I->click('thead tr:first-child th:nth-child(7) > a');
    
    $I->seeTableRowOrder(['Status Test Issue 2 (Feature)',          // Low    | Tiny    | Open        |   0
                          'Status Test Issue 5 (Feature)',          // Low    | Small   | Open        |   0
                          'Status Test Issue 6 (Feature)',          // Low    | Small   | In Progress |  10
                          'Assigned Test Issue 6 (Feature)',        // Low    | Small   | Blocked     |  30
                          'Assigned Test Issue 7 (Feature)',        // Low    | Small   | Blocked     |  40
                          'Assigned Test Issue 8 (Feature)',        // Low    | Small   | Blocked     |  50
                          'Milestone 3 Test Issue 1 (Feature)',     // Low    | Medium  | Open        |   0
                          'Status Test Issue 8 (Feature)',          // Low    | Large   | In Progress |  20
                          'Status Test Issue 7 (Feature)',          // Low    | Large   | In Progress |  20
                          'Milestone 3 Test Issue 2 (Enhancement)', // Low    | Massive | Open        |   0
                          'Status Test Issue 3 (Feature)',          // Medium | Tiny    | Open        |   0
                          'Milestone 1 Test Issue 1 (Feature)',     // Medium | Small   | Finished    | 100
                          'Status Test Issue 12 (Feature)',         // Medium | Small   | Finished    | 100
                          'Assigned Test Issue 2 (Task)',           // Medium | Medium  | In Progress |   0
                          'Assigned Test Issue 1 (Task)']);         // Medium | Medium  | Open        |   0
    
    $I->click('thead tr:first-child th:nth-child(7) > a');
    
    $I->seeTableRowOrder(['Status Test Issue 2 (Feature)',          // Low    | Tiny    | Open        |   0
                          'Assigned Test Issue 8 (Feature)',        // Low    | Small   | Blocked     |  50
                          'Assigned Test Issue 7 (Feature)',        // Low    | Small   | Blocked     |  40
                          'Assigned Test Issue 6 (Feature)',        // Low    | Small   | Blocked     |  30
                          'Status Test Issue 6 (Feature)',          // Low    | Small   | In Progress |  10
                          'Status Test Issue 5 (Feature)',          // Low    | Small   | Open        |   0
                          'Milestone 3 Test Issue 1 (Feature)',     // Low    | Medium  | Open        |   0
                          'Status Test Issue 8 (Feature)',          // Low    | Large   | In Progress |  20
                          'Status Test Issue 7 (Feature)',          // Low    | Large   | In Progress |  20
                          'Milestone 3 Test Issue 2 (Enhancement)', // Low    | Massive | Open        |   0
                          'Status Test Issue 3 (Feature)',          // Medium | Tiny    | Open        |   0
                          'Milestone 1 Test Issue 1 (Feature)',     // Medium | Small   | Finished    | 100
                          'Status Test Issue 12 (Feature)',         // Medium | Small   | Finished    | 100
                          'Milestone 2 Test Issue 3 (Enhancement)', // Medium | Medium  | Finished    | 100
                          'Status Test Issue 13 (Feature)']);       // Medium | Medium  | Rejected    | 100
  }
}

?>
