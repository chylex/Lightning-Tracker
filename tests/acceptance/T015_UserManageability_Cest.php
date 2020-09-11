<?php
declare(strict_types = 1);

namespace acceptance;

use AcceptanceTester;
use Helper\Acceptance;

class T015_UserManageability_Cest{
  private const ROWS = [
      'Admin'      => 1,
      'Moderator'  => 2,
      'Manager1'   => 3,
      'Manager2'   => 4,
      'User1'      => 5,
      'User2'      => 6,
      'Test'       => 7,
      'Admin2'     => 8,
      'Moderator2' => 9,
      'Special1'   => 10
  ];
  
  private function startManagingAs(AcceptanceTester $I, string $user): void{
    $I->amLoggedIn($user);
    $I->amOnPage('/users');
  }
  
  private function ensureCanOnlyManage(AcceptanceTester $I, array $users): void{
    foreach($users as $user){
      $I->seeElement('tbody tr:nth-child('.self::ROWS[$user].') a[href^="http://localhost/users/"]');
      $I->seeElement('tbody tr:nth-child('.self::ROWS[$user].') form[action$="/delete"]');
    }
    
    foreach(array_diff(array_keys(self::ROWS), $users) as $user){
      $I->dontSeeElement('tbody tr:nth-child('.self::ROWS[$user].') a[href^="http://localhost/users/"]');
      $I->dontSeeElement('tbody tr:nth-child('.self::ROWS[$user].') form[action$="/delete"]');
    }
  }
  
  public function prepareTemporaryUsers(): void{
    $db = Acceptance::getDB();
    
    $moderator = $db->query('SELECT id FROM system_roles WHERE title = \'Moderator\'')->fetchColumn();
    $special = $db->query('SELECT id FROM system_roles WHERE title = \'Special1\'')->fetchColumn();
    
    $db->exec(<<<SQL
INSERT INTO users (id, name, email, password, role_id, admin, date_registered)
VALUES ('aaaaaaaaa', 'Admin2', 'a', '', NULL, TRUE, DATE_ADD(NOW(), INTERVAL 1 SECOND)),
       ('bbbbbbbbb', 'Moderator2', 'b', '', $moderator, FALSE, DATE_ADD(NOW(), INTERVAL 2 SECOND)),
       ('ccccccccc', 'Special1', 'c', '', $special, FALSE, DATE_ADD(NOW(), INTERVAL 3 SECOND))
SQL
    );
  }
  
  /**
   * @depends prepareTemporaryUsers
   */
  public function adminCanManageAllButAdminsOrSpecials(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'Admin');
    
    $this->ensureCanOnlyManage($I, ['Moderator',
                                    'User1',
                                    'User2',
                                    'Test',
                                    'Moderator2',
                                    'Manager1',
                                    'Manager2']);
  }
  
  /**
   * @depends prepareTemporaryUsers
   */
  public function moderatorCanOnlyManageLowerRoles(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'Moderator');
    
    $this->ensureCanOnlyManage($I, ['User1',
                                    'User2',
                                    'Test',
                                    'Manager1',
                                    'Manager2']);
  }
  
  /**
   * @depends prepareTemporaryUsers
   */
  public function manager1CanOnlyManageLowerRoles(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'Manager1');
    
    $this->ensureCanOnlyManage($I, ['User1',
                                    'User2',
                                    'Test',
                                    'Manager2']);
  }
  
  /**
   * @depends prepareTemporaryUsers
   */
  public function manager2CanOnlyManageLowerRoles(AcceptanceTester $I): void{
    $this->startManagingAs($I, 'Manager2');
    
    $this->ensureCanOnlyManage($I, ['User1',
                                    'User2',
                                    'Test']);
  }
  
  /**
   * @depends adminCanManageAllButAdminsOrSpecials
   * @depends moderatorCanOnlyManageLowerRoles
   * @depends manager1CanOnlyManageLowerRoles
   * @depends manager2CanOnlyManageLowerRoles
   */
  public function removeTemporaryUsers(): void{
    $db = Acceptance::getDB();
    $db->exec('DELETE FROM users WHERE email IN (\'a\', \'b\', \'c\')');
  }
}

?>
