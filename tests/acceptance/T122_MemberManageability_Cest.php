<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;
use PDO;

class T122_MemberManageability_Cest{
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
  
  private function assignUser3Role(?string $role): void{
    $db = Acceptance::getDB();
    
    if ($role === null){
      $db->exec(<<<SQL
UPDATE project_members
SET role_id = NULL
WHERE user_id = 'user3test' AND project_id = (SELECT p.id FROM projects p WHERE p.url = 'p1')
SQL
      );
    }
    else{
      $db->exec(<<<SQL
UPDATE project_members
SET role_id = (SELECT pr.role_id FROM project_roles pr WHERE pr.title = '$role' AND pr.project_id = (SELECT p.id FROM projects p WHERE p.url = 'p1'))
WHERE user_id = 'user3test' AND project_id = (SELECT p.id FROM projects p WHERE p.url = 'p1')
SQL
      );
    }
  }
  
  private function startManagingAs(AcceptanceTester $I, string $user): void{
    $I->amLoggedIn($user);
    $I->amOnPage('/project/p1/members');
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function ensureCanOnlyManage(AcceptanceTester $I, array $rows, array $users): void{
    $db = Acceptance::getDB();
    $user_ids = $db->query('SELECT name, id FROM users')->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach($users as $user){
      $I->seeElement('tbody tr:nth-child('.$rows[$user].') a[href^="http://localhost/project/p1/members/"]');
      $I->seeElement('tbody tr:nth-child('.$rows[$user].') form[action$="/remove"]');
    }
    
    $missing = array_diff(array_keys($rows), $users);
    
    foreach($missing as $user){
      $I->dontSeeElement('tbody tr:nth-child('.$rows[$user].') a[href^="http://localhost/project/p1/members/"]');
      $I->dontSeeElement('tbody tr:nth-child('.$rows[$user].') form[action$="/remove"]');
    }
    
    foreach($users as $user){
      $I->amOnPage('/project/p1/members/'.$user_ids[$user]);
      $I->dontSee('Permission Error', 'h2');
    }
    
    foreach($missing as $user){
      $id = $user_ids[$user];
      
      foreach([$id, $id.'/remove'] as $suffix){
        $I->amOnPage('/project/p1/members/'.$suffix);
        $I->see('Permission Error', 'h2');
      }
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
