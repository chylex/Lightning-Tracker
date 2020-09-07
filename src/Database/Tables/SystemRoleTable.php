<?php
declare(strict_types = 1);

namespace Database\Tables;

use Database\AbstractTable;
use Database\Objects\RoleInfo;
use Exception;
use PDOException;

final class SystemRoleTable extends AbstractTable{
  /**
   * @param string $title
   * @param bool $special
   * @return int
   * @throws Exception
   */
  public function addRole(string $title, bool $special = false): int{
    $this->db->beginTransaction();
    
    try{
      $this->execute('INSERT INTO system_roles (title, special) VALUES (?, ?)',
                     'SB', [$title, $special]);
      
      $id = $this->getLastInsertId();
      
      if ($id === null){
        $this->db->rollBack();
        throw new Exception('Could not retrieve role ID.');
      }
      
      $this->db->commit();
      return $id;
    }catch(PDOException $e){
      $this->db->rollBack();
      throw $e;
    }
  }
  
  public function editRole(int $id, string $title): void{
    $this->execute('UPDATE system_roles SET title = ? WHERE id = ?',
                   'SI', [$title, $id]);
  }
  
  /**
   * @return RoleInfo[]
   */
  public function listRoles(): array{
    $stmt = $this->db->query('SELECT id, title, special FROM system_roles ORDER BY special DESC, id ASC');
    return $this->fetchMap($stmt, fn($v): RoleInfo => new RoleInfo($v['id'], $v['title'], 0, (bool)$v['special']));
  }
  
  public function getRoleIdByTitle(string $title): ?int{
    $stmt = $this->execute('SELECT id FROM system_roles WHERE title = ?',
                           'S', [$title]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function getRoleTitleIfNotSpecial(int $id): ?string{
    $stmt = $this->execute('SELECT title FROM system_roles WHERE id = ? AND special = FALSE',
                           'I', [$id]);
    
    return $this->fetchOneColumn($stmt);
  }
  
  public function deleteById(int $id): void{
    $this->execute('DELETE FROM system_roles WHERE id = ? AND special = FALSE',
                   'I', [$id]);
  }
}

?>
