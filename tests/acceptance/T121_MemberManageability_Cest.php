<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T121_MemberManageability_Cest{
  private const ROWS_USER3_ROLELESS = [
      'User1'    => 1,
      'Manager1' => 2,
      'Manager2' => 3,
      'User2'    => 4,
      'RoleLess' => 5,
      'User3'    => 6,
  ];
  
  private const ROWS_USER3_ADMINISTRATOR = [
      'User1'    => 1,
      'Manager1' => 2,
      'User3'    => 3,
      'Manager2' => 4,
      'User2'    => 5,
      'RoleLess' => 6,
  ];
  
  private const ROWS_USER3_MODERATOR_OR_DEVELOPER = [
      'User1'    => 1,
      'Manager1' => 2,
      'Manager2' => 3,
      'User3'    => 4,
      'User2'    => 5,
      'RoleLess' => 6,
  ];
  
  private const ROLES = [
      'Owner'         => 1,
      'Administrator' => 2,
      'Moderator'     => 3,
      'Developer'     => 4,
      'Reporter'      => 5,
  ];
  
  private function assignUser3Role(?string $role): void{
    $db = Acceptance::getDB();
    $db->exec('UPDATE project_members SET role_id = '.($role === null ? 'NULL' : self::ROLES[$role]).' WHERE user_id = \'user3test\' AND project_id = (SELECT id FROM projects WHERE url = \'p1\')');
  }
  
  private function startManagingAs(AcceptanceTester $I, string $user): void{
    $I->amLoggedIn($user);
    $I->amOnPage('/project/p1/members');
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function ensureCanOnlyManage(AcceptanceTester $I, array $rows, array $users): void{
    foreach($users as $user){
      $I->seeElement('tbody tr:nth-child('.$rows[$user].') a[href^="http://localhost/project/p1/members/"]');
      $I->seeElement('tbody tr:nth-child('.$rows[$user].') form[action$="/remove"]');
    }
    
    foreach(array_diff(array_keys($rows), $users) as $user){
      $I->dontSeeElement('tbody tr:nth-child('.$rows[$user].') a[href^="http://localhost/project/p1/members/"]');
      $I->dontSeeElement('tbody tr:nth-child('.$rows[$user].') form[action$="/remove"]');
    }
  }
  
  public function adminCanManageAllButOwnerDespiteNotBeingAMember(AcceptanceTester $I): void{
    // TODO currently failing because the role check doesn't work for system admins
    $this->startManagingAs($I, 'Admin');
    $I->dontSee('Admin', 'table td:first-child');
    
    $this->ensureCanOnlyManage($I, self::ROWS_USER3_ROLELESS, [
        'Manager1',
        'Manager2',
        'User2',
        'RoleLess',
        'User3',
    ]);
  }
  
  public function ownerCanManageAllButSelf(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'User1');
    
    $this->ensureCanOnlyManage($I, self::ROWS_USER3_ROLELESS, [
        'Manager1',
        'Manager2',
        'User2',
        'RoleLess',
        'User3',
    ]);
  }
  
  public function memberWithAdministratorRoleCanOnlyManageLowerRoles(AcceptanceTester $I): void{
    $this->assignUser3Role('Administrator');
    $this->startManagingAs($I, 'Manager1');
    
    $this->ensureCanOnlyManage($I, self::ROWS_USER3_ADMINISTRATOR, [
        'Manager2',
        'User2',
        'RoleLess',
    ]);
  }
  
  public function memberWithModeratorRoleCanOnlyManageLowerRoles(AcceptanceTester $I): void{
    $this->assignUser3Role('Moderator');
    $this->startManagingAs($I, 'Manager2');
    
    $this->ensureCanOnlyManage($I, self::ROWS_USER3_MODERATOR_OR_DEVELOPER, [
        'User2',
        'RoleLess',
    ]);
  }
  
  public function memberWithDeveloperRoleCannotManageAnyone(AcceptanceTester $I): void{
    $this->assignUser3Role('Developer');
    $this->startManagingAs($I, 'User3');
    $this->ensureCanOnlyManage($I, self::ROWS_USER3_MODERATOR_OR_DEVELOPER, []);
  }
  
  /**
   * @depends memberWithAdministratorRoleCanOnlyManageLowerRoles
   * @depends memberWithModeratorRoleCanOnlyManageLowerRoles
   * @depends memberWithDeveloperRoleCannotManageAnyone
   */
  public function resetUser3Role(): void{
    $this->assignUser3Role(null);
  }
}

?>
