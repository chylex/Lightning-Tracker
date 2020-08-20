<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\UserFilter;
use Database\Objects\UserInfo;
use Database\Objects\UserLoginInfo;
use Database\Objects\UserStatistics;
use Exception;
use PDO;

final class UserTable extends AbstractTable{
  public function __construct(PDO $db){
    parent::__construct($db);
  }
  
  /**
   * @param string $name
   * @param string $email
   * @param string $password
   * @throws Exception
   */
  public function addUser(string $name, string $email, string $password): void{
    $stmt = $this->db->prepare('INSERT INTO users (name, email, password, date_registered) VALUES (?, ?, ?, NOW())');
    $stmt->bindValue(1, $name);
    $stmt->bindValue(2, $email);
    $stmt->bindValue(3, UserLoginInfo::hashPassword($password));
    $stmt->execute();
  }
  
  /**
   * @param int $id
   * @param string $name
   * @param string $email
   * @param string|null $password If null, the password will not be changed.
   * @param int|null $role_id
   * @throws Exception
   */
  public function editUser(int $id, string $name, string $email, ?string $password, ?int $role_id): void{
    $stmt = $this->db->prepare(<<<SQL
UPDATE users
SET name = ?, email = ?, password = IFNULL(?, password), role_id = ?
WHERE id = ?
SQL
    );
    
    $stmt->bindValue(1, $name);
    $stmt->bindValue(2, $email);
    $stmt->bindValue(3, $password === null ? null : UserLoginInfo::hashPassword($password));
    $stmt->bindValue(4, $role_id, $role_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(5, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
  
  /**
   * @param int $id
   * @param string $password
   * @throws Exception
   */
  public function changePassword(int $id, string $password): void{
    $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->bindValue(1, UserLoginInfo::hashPassword($password));
    $stmt->bindValue(2, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function countUsers(?UserFilter $filter = null): ?int{
    $filter ??= UserFilter::empty();
    
    if ($filter->isEmpty()){
      $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM users', AbstractFilter::STMT_COUNT);
    }
    else{
      $stmt = $filter->prepare($this->db, 'SELECT COUNT(*) FROM users LEFT JOIN system_roles sr ON sr.id = users.role_id', AbstractFilter::STMT_COUNT);
    }
    
    $stmt->execute();
    
    $count = $this->fetchOneColumn($stmt);
    return $count === false ? null : (int)$count;
  }
  
  /**
   * @param UserFilter|null $filter
   * @return UserInfo[]
   */
  public function listUsers(?UserFilter $filter = null): array{
    $filter ??= UserFilter::empty();
    
    $sql = <<<SQL
SELECT u.id, u.name, u.email, sr.id AS role_id, sr.title AS role_title, u.admin, u.date_registered
FROM users u
LEFT JOIN system_roles sr ON u.role_id = sr.id
SQL;
    
    $stmt = $filter->prepare($this->db, $sql);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new UserInfo($res['id'], $res['name'], $res['email'], $res['role_id'], $res['role_title'], (bool)$res['admin'], $res['date_registered']);
    }
    
    return $results;
  }
  
  public function getUserInfo(int $id): ?UserInfo{
    $sql = <<<SQL
SELECT u.id, u.name, u.email, sr.id AS role_id, sr.title AS role_title, u.admin, u.date_registered
FROM users u
LEFT JOIN system_roles sr ON u.role_id = sr.id
WHERE u.id = ?
SQL;
    
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new UserInfo($res['id'], $res['name'], $res['email'], $res['role_id'], $res['role_title'], (bool)$res['admin'], $res['date_registered']);
  }
  
  public function getLoginInfo(string $name): ?UserLoginInfo{
    $stmt = $this->db->prepare('SELECT id, password FROM users WHERE name = ?');
    $stmt->execute([$name]);
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new UserLoginInfo($res['id'], $res['password']);
  }
  
  public function getUserStatistics(int $id): UserStatistics{
    $numbers = [];
    
    foreach(['SELECT COUNT(*) FROM tracker_members WHERE user_id = ?',
             'SELECT COUNT(*) FROM issues WHERE author_id = ?',
             'SELECT COUNT(*) FROM issues WHERE assignee_id = ?'] as $sql){
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(1, $id, PDO::PARAM_INT);
      $stmt->execute();
      
      $res = $this->fetchOneColumn($stmt);
      $numbers[] = $res === false ? 0 : (int)$res;
    }
    
    return new UserStatistics($numbers[0], $numbers[1], $numbers[2]);
  }
  
  public function findIdByName(string $name): ?int{
    $stmt = $this->db->prepare('SELECT id FROM users WHERE name = ?');
    $stmt->execute([$name]);
    
    $id = $this->fetchOneColumn($stmt);
    return $id === false ? null : (int)$id;
  }
  
  public function findIdByEmail(string $email): ?int{
    $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    $id = $this->fetchOneColumn($stmt);
    return $id === false ? null : (int)$id;
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM users WHERE id = ? AND admin = FALSE');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
