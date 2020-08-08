<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Objects\RoleInfo;
use Database\Objects\UserProfile;
use Database\Tables\Traits\PermTable;
use Pages\Models\Root\TrackersModel;
use PDO;
use PDOException;

final class SystemPermTable extends AbstractTable{
  use PermTable;
  
  private const GUEST_PERMS = [TrackersModel::PERM_LIST];
  private const LOGON_PERMS = [TrackersModel::PERM_LIST];
  
  public function __construct(PDO $db){
    parent::__construct($db);
  }
  
  protected function getDB(): PDO{
    return $this->db;
  }
  
  public function addRole(string $title, array $perms): void{
    $this->db->beginTransaction();
    
    try{
      $stmt = $this->db->prepare('INSERT INTO system_roles (title) VALUES (?)');
      $stmt->execute([$title]);
      
      $this->addPermissions('INSERT INTO system_role_perms (role_id, permission) VALUES ()', $perms);
      
      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->prepare('SELECT id, title FROM system_roles ORDER BY id ASC');
    $stmt->execute();
    return $this->fetchRoles($stmt);
  }
  
  /**
   * @param ?UserProfile $user
   * @return string[]
   */
  public function listPerms(?UserProfile $user): array{
    if ($user === null){
      return self::GUEST_PERMS;
    }
    
    if ($user->getRoleId() === null){
      return self::LOGON_PERMS;
    }
    
    $stmt = $this->db->prepare('SELECT permission FROM system_role_perms WHERE role_id = ?');
    $stmt->bindValue(1, $user->getRoleId(), PDO::PARAM_INT);
    $stmt->execute();
    return $this->fetchPerms($stmt);
  }
  
  public function deleteById(int $id): void{
    $stmt = $this->db->prepare('DELETE FROM system_roles WHERE id = ?');
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
  }
}

?>
