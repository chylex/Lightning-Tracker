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
    
    $t = new T003_AdminLogin_Cest();
    self::t($I, $t, fn() => $t->login($I));
    
    $t = new T006_RegisterAccounts_Cest();
    self::t($I, $t, fn() => $t->registerModeratorWithLogin($I));
    self::t($I, $t, fn() => $t->registerUser1WithLogin($I));
    self::t($I, $t, fn() => $t->registerUser2WithLogin($I));
    self::t($I, $t, fn() => $t->setupRoles());
    
    $t = new T007_CreateProjects_Cest();
    self::t($I, $t, fn() => $t->createProjectsAsAdmin($I));
    self::t($I, $t, fn() => $t->createProjectsAsUser1($I));
    self::t($I, $t, fn() => $t->createProjectsAsUser2($I));
    
    $t = new T011_SystemSettingsRoles_Cest();
    self::t($I, $t, fn() => $t->createAdditionalRoles($I));
    
    $t = new T012_SystemSettingsRolesSpecial_Cest();
    self::t($I, $t, fn() => $t->createSpecialRoles($I));
    
    $t = new T013_SystemSettingsRoleEditing_Cest();
    self::t($I, $t, fn() => $t->setAllPermissionsForRole1($I));
    self::t($I, $t, fn() => $t->setSomePermissionsForRole2($I));
  }
}

?>
