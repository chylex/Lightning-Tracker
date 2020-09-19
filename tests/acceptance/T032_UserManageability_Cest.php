<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;
use PDO;

class T032_UserManageability_Cest{
  private const ROWS = [
      'Admin'      => 1,
      'Moderator'  => 2,
      'Manager1'   => 3,
      'Manager2'   => 4,
      'User1'      => 5,
      'User3'      => 6,
      'User2'      => 7,
      'RoleLess'   => 8,
      'Admin2'     => 9,
      'Moderator2' => 10,
  ];
  
  private function startManagingAs(AcceptanceTester $I, string $user): void{
    $I->amLoggedIn($user);
    $I->amOnPage('/users');
    $I->dontSee('Permission Error', 'h2');
  }
  
  private function ensureCanOnlyManage(AcceptanceTester $I, array $users): void{
    $db = Acceptance::getDB();
    $user_ids = $db->query('SELECT name, id FROM users')->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach($users as $user){
      $I->seeElement('tbody tr:nth-child('.self::ROWS[$user].') a[href^="http://localhost/users/"]');
      $I->seeElement('tbody tr:nth-child('.self::ROWS[$user].') form[action$="/delete"]');
    }
    
    $missing = array_diff(array_keys(self::ROWS), $users);
    
    foreach($missing as $user){
      $tr = 'tbody tr:nth-child('.self::ROWS[$user].')';
      $I->dontSeeElement($tr.' a[href^="http://localhost/users/"]');
      $I->dontSeeElement($tr.' form[action$="/delete"]');
    }
    
    foreach($users as $user){
      $id = $user_ids[$user];
      
      foreach([$id, $id.'/delete'] as $suffix){
        $I->amOnPage('/users/'.$suffix);
        $I->dontSee('Permission Error', 'h2');
      }
    }
    
    foreach($missing as $user){
      $id = $user_ids[$user];
      
      foreach([$id, $id.'/delete'] as $suffix){
        $I->amOnPage('/users/'.$suffix);
        $I->see('Permission Error', 'h2');
      }
    }
  }
  
  public function prepareTemporaryUsers(): void{
    $db = Acceptance::getDB();
    
    $admin = $db->query('SELECT id FROM system_roles WHERE title = \'Admin\'')->fetchColumn();
    $moderator = $db->query('SELECT id FROM system_roles WHERE title = \'Moderator\'')->fetchColumn();
    
    $db->exec(<<<SQL
INSERT INTO users (id, name, email, password, role_id, date_registered)
VALUES ('aaaaaaaaa', 'Admin2', 'a', '', $admin, DATE_ADD(NOW(), INTERVAL 1 SECOND)),
       ('bbbbbbbbb', 'Moderator2', 'b', '', $moderator, DATE_ADD(NOW(), INTERVAL 2 SECOND))
SQL
    );
  }
  
  /**
   * @depends prepareTemporaryUsers
   */
  public function adminCanManageAllButAdminsOrSpecials(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'Admin');
    
    $this->ensureCanOnlyManage($I, [
        'Manager1',
        'Manager2',
        'Moderator',
        'Moderator2',
        'RoleLess',
        'User1',
        'User3',
        'User2',
    ]);
  }
  
  /**
   * @depends prepareTemporaryUsers
   */
  public function moderatorCanOnlyManageLowerRoles(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'Moderator');
    
    $this->ensureCanOnlyManage($I, [
        'Manager1',
        'Manager2',
        'RoleLess',
        'User1',
        'User3',
        'User2',
    ]);
  }
  
  /**
   * @depends prepareTemporaryUsers
   */
  public function manager1CanOnlyManageLowerRoles(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'Manager1');
    
    $this->ensureCanOnlyManage($I, [
        'Manager2',
        'RoleLess',
        'User1',
        'User3',
        'User2',
    ]);
  }
  
  /**
   * @depends prepareTemporaryUsers
   */
  public function manager2CanOnlyManageLowerRoles(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'Manager2');
    
    $this->ensureCanOnlyManage($I, [
        'RoleLess',
        'User1',
        'User3',
        'User2',
    ]);
  }
  
  /**
   * @depends adminCanManageAllButAdminsOrSpecials
   * @depends moderatorCanOnlyManageLowerRoles
   * @depends manager1CanOnlyManageLowerRoles
   * @depends manager2CanOnlyManageLowerRoles
   */
  public function removeTemporaryUsers(): void{
    $db = Acceptance::getDB();
    $db->exec('DELETE FROM users WHERE email IN (\'a\', \'b\')');
  }
}

?>
