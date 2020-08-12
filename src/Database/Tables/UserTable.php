<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Filters\Types\UserFilter;
use Database\Objects\UserInfo;
use Database\Objects\UserLoginInfo;
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
      $stmt = $this->db->prepare('SELECT COUNT(*) FROM users '.$filter->generateClauses(true));
    }
    else{
      $stmt = $this->db->prepare('SELECT COUNT(*) FROM users LEFT JOIN system_roles sr ON sr.id = users.role_id '.$filter->generateClauses(true));
    }
    
    $filter->prepareStatement($stmt);
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
SELECT u.id, u.name, u.email, sr.title AS role_title, u.date_registered
FROM users u
LEFT JOIN system_roles sr ON u.role_id = sr.id
SQL;
    
    $stmt = $this->db->prepare($sql.' '.$filter->generateClauses());
    $filter->prepareStatement($stmt);
    $stmt->execute();
    
    $results = [];
    
    while(($res = $this->fetchNext($stmt)) !== false){
      $results[] = new UserInfo($res['id'], $res['name'], $res['email'], $res['role_title'], $res['date_registered']);
    }
    
    return $results;
  }
  
  public function getLoginInfo(string $name): ?UserLoginInfo{
    $stmt = $this->db->prepare('SELECT id, password FROM users WHERE name = ?');
    $stmt->execute([$name]);
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new UserLoginInfo($res['id'], $res['password']);
  }
  
  private function checkExists(string $field, string $value): bool{
    $stmt = $this->db->prepare('SELECT 1 FROM users WHERE '.$field.' = ?');
    $stmt->execute([$value]);
    return (bool)$this->fetchOneColumn($stmt);
  }
  
  public function checkNameExists(string $name): bool{
    return $this->checkExists('name', $name);
  }
  
  public function checkEmailExists(string $email): bool{
    return $this->checkExists('email', $email);
  }
  
  public function findIdByName(string $name): ?int{
    $stmt = $this->db->prepare('SELECT id FROM users WHERE name = ?');
    $stmt->execute([$name]);
    
    $id = $this->fetchOneColumn($stmt);
    return $id === false ? null : (int)$id;
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
