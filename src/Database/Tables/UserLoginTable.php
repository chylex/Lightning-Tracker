<?php
declare(strict_types = 1);

namespace Database\Tables;

use Data\UserId;
use Database\AbstractTable;
use Database\Objects\UserProfile;
use PDO;

final class UserLoginTable extends AbstractTable{
  // TODO periodically delete expired tokens
  
  public function checkLogin(string $token): ?UserProfile{
    $stmt = $this->db->prepare(<<<SQL
SELECT u.id, u.name, u.email, u.role_id, u.admin
FROM users u
JOIN user_logins ul ON u.id = ul.id
WHERE token = ? AND NOW() < ul.expires
SQL
    );
    
    $stmt->execute([$token]);
    
    $res = $this->fetchOne($stmt);
    return $res === false ? null : new UserProfile($res['id'], $res['name'], $res['email'], $res['role_id'], (bool)$res['admin']);
  }
  
  public function addOrRenewToken(UserId $id, string $token, int $expire_in_minutes): void{
    $stmt = $this->db->prepare(<<<SQL
INSERT INTO user_logins (id, token, expires)
VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))
ON DUPLICATE KEY UPDATE expires = GREATEST(expires, VALUES(expires))
SQL
    );
    
    $stmt->bindValue(1, $id);
    $stmt->bindValue(2, $token);
    $stmt->bindValue(3, $expire_in_minutes, PDO::PARAM_INT);
    $stmt->execute();
  }
  
  public function renewToken(string $token, int $expire_in_minutes): void{
    $stmt = $this->db->prepare('UPDATE user_logins SET expires = GREATEST(expires, DATE_ADD(NOW(), INTERVAL ? MINUTE)) WHERE token = ?');
    $stmt->bindValue(1, $expire_in_minutes, PDO::PARAM_INT);
    $stmt->bindValue(2, $token);
    $stmt->execute();
  }
  
  public function destroyToken(string $token): void{
    $this->db->prepare('DELETE FROM user_logins WHERE token = ? LIMIT 1')->execute([$token]);
  }
}

?>
