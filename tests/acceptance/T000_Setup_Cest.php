<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T000_Setup_Cest{
  private static function t(AcceptanceTester $I, $t, $call): void{
    if (method_exists($t, '_before')){
      $t->_before($I);
    }
    
    $call();
    $I->amNotLoggedIn();
    
    if (method_exists($t, '_after')){
      $t->_after($I);
    }
  }
  
  public function _failed(AcceptanceTester $I): void{
    $I->terminate();
  }
  
  public function run(AcceptanceTester $I): void{
    if (!Acceptance::isInGroup('core')){
      return;
    }
    
    $t = new T001_Install_Cest();
    self::t($I, $t, fn() => $t->install($I, true));
    
    $t = new T002_AdminLogin_Cest();
    self::t($I, $t, fn() => $t->login($I));
    
    $t = new T004_RegisterAccounts_Cest();
    self::t($I, $t, fn() => $t->registerModeratorWithLogin($I));
    self::t($I, $t, fn() => $t->registerManager1WithLogin($I));
    self::t($I, $t, fn() => $t->registerManager2WithLogin($I));
    self::t($I, $t, fn() => $t->registerUser1WithLogin($I));
    self::t($I, $t, fn() => $t->registerUser2WithLogin($I));
    self::t($I, $t, fn() => $t->registerUser3WithLogin($I));
    self::t($I, $t, fn() => $t->registerRoleLessWithLogin($I));
    self::t($I, $t, fn() => $t->setupRoles());
    
    $t = new T005_CreateProjects_Cest();
    self::t($I, $t, fn() => $t->createProjectsAsAdmin($I));
    self::t($I, $t, fn() => $t->createProjectsAsUser1($I));
    self::t($I, $t, fn() => $t->createProjectsAsUser2($I));
    
    $t = new T100_SetupProjects_Cest();
    self::t($I, $t, fn() => $t->run($I));
    
    $t = new T101_InviteMembers_Cest();
    self::t($I, $t, fn() => $t->inviteMembersInProject1($I));
    self::t($I, $t, fn() => $t->inviteMembersInProject2($I));
    
    $t = new T102_CreateMilestones_Cest();
    self::t($I, $t, fn() => $t->createMilestonesInProject1($I));
    self::t($I, $t, fn() => $t->createMilestonesInProject2($I));
    
    $t = new T103_CreateIssues_Cest();
    self::t($I, $t, fn() => $t->createIssuesInProject1($I));
    self::t($I, $t, fn() => $t->createIssuesInProject2($I));
    self::t($I, $t, fn() => $t->setupCreationDateOrder());
  }
}

?>
