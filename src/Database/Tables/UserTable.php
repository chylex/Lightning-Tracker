<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractTable;
use Database\Filters\AbstractFilter;
use Database\Filters\Types\UserFilter;
use Database\Objects\UserInfo;
use Database\Objects\UserLoginInfo;
use Database\Objects\UserStatistics;
use Exception;
use PDO;

final class UserTable extends AbstractTable{
  /**
   * @param string $name
   * @param string $email
   * @param string $password
   * @throws Exception
   */
  public function addUser(string $name, string $email, string $password): void{
    $id = null;
    
    for($attempt = 0; $attempt < 100; $attempt++){
      $id = UserId::generateNew();
      
      $stmt = $this->db->prepare('SELECT 1 FROM users WHERE id = ?');
      $stmt->execute([$id]);
      
      if ($this->fetchOne($stmt) === false){
        break;
      }
      else{
        $id = null;
      }
    }
    
    if ($id === null){
      throw new Exception('Could not generate a unique user ID.');
    }
    
    $stmt = $this->db->prepare('INSERT INTO users (id, name, email, password, date_registered) VALUES (?, ?, ?, ?, NOW())');
    $stmt->bindValue(1, $id);
    $stmt->bindValue(2, $name);
    $stmt->bindValue(3, $email);
    $stmt->bindValue(4, UserLoginInfo::hashPassword($password));
    $stmt->execute();
  }
  
  /**
   * @param UserId $id
   * @param string $name
   * @param string|null $email If null, the email will not be changed.
   * @param string|null $password If null, the password will not be changed.
   * @param int|null $role_id
   * @throws Exception
   */
  public function editUser(UserId $id, string $name, ?string $email, ?string $password, ?int $role_id): void{
    $stmt = $this->db->prepare(<<<SQL
UPDATE users
SET name = ?, email = IFNULL(?, email), password = IFNULL(?, password), role_id = ?
WHERE id = ?
SQL
    );
    
    $stmt->bindValue(1, $name);
    $stmt->bindValue(2, $email);
    $stmt->bindValue(3, $password === null ? null : UserLoginInfo::hashPassword($password));
    $stmt->bindValue(4, $role_id, $role_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(5, $id);
    $stmt->execute();
  }
  
  /**
   * @param UserId $id
   * @param string $password
   * @throws Exception
   */
  public function changePassword(UserId $id, string $password): void{
    $stmt = $this->db->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([UserLoginInfo::hashPassword($password), $id]);
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
  
  public function getUserName(UserId $id): ?string{
    $stmt = $this->db->prepare('SELECT name FROM users WHERE id = ?');
    $stmt->execute([$id]);
    
    $res = $this->fetchOneColumn($stmt);
    return $res === false ? null : $res;
  }
  
  public function getUserInfo(UserId $id): ?UserInfo{
    $sql = <<<SQL
SELECT u.id, u.name, u.email, sr.id AS role_id, sr.title AS role_title, u.admin, u.date_registered
FROM users u
LEFT JOIN system_roles sr ON u.role_id = sr.id
WHERE u.id = ?
SQL;
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id]);
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new UserInfo($res['id'], $res['name'], $res['email'], $res['role_id'], $res['role_title'], (bool)$res['admin'], $res['date_registered']);
  }
  
  public function getLoginInfo(string $name): ?UserLoginInfo{
    $stmt = $this->db->prepare('SELECT id, password FROM users WHERE name = ?');
    $stmt->execute([$name]);
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new UserLoginInfo($res['id'], $res['password']);
  }
  
  public function getUserStatistics(UserId $id): UserStatistics{
    $numbers = [];
    
    foreach(['SELECT COUNT(*) FROM project_members WHERE user_id = ?',
             'SELECT COUNT(*) FROM issues WHERE author_id = ?',
             'SELECT COUNT(*) FROM issues WHERE assignee_id = ?'] as $sql){
      $stmt = $this->db->prepare($sql);
      $stmt->execute([$id]);
      
      $res = $this->fetchOneColumn($stmt);
      $numbers[] = $res === false ? 0 : (int)$res;
    }
    
    return new UserStatistics($numbers[0], $numbers[1], $numbers[2]);
  }
  
  public function findIdByName(string $name): ?UserId{
    $stmt = $this->db->prepare('SELECT id FROM users WHERE name = ?');
    $stmt->execute([$name]);
    
    $id = $this->fetchOneColumn($stmt);
    return $id === false ? null : UserId::fromRaw($id);
  }
  
  public function findIdByEmail(string $email): ?UserId{
    $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    $id = $this->fetchOneColumn($stmt);
    return $id === false ? null : UserId::fromRaw($id);
  }
  
  public function deleteById(UserId $id): void{
    $stmt = $this->db->prepare('DELETE FROM users WHERE id = ? AND admin = FALSE');
    $stmt->execute([$id]);
  }
}

?>
