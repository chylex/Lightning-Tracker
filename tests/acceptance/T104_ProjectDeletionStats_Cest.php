<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;

class T104_ProjectDeletionStats_Cest{
  public function checkStatisticsForProject1(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/project/p1/delete');
    $I->see('30 issues', 'li');
    $I->see('4 milestones', 'li');
    $I->see('6 members', 'li');
  }
  
  public function checkStatisticsForProject2(AcceptanceTester $I): void{
    $I->amLoggedIn('Admin');
    $I->amOnPage('/project/p2/delete');
    $I->see('10 issues', 'li');
    $I->see('4 milestones', 'li');
    $I->see('4 members', 'li');
  }
}

?>
