<?php
declare(strict_types = 1);

namespace Update\Migrations;

use Update\AbstractMigrationProcess;

final class Migration9 extends AbstractMigrationProcess{
  /** @noinspection SqlResolve */
  public function getTasks(): array{
    return [
        self::sql('ALTER TABLE system_roles ADD type ENUM (\'normal\', \'admin\') NOT NULL DEFAULT \'normal\' AFTER id'),
        self::sql('ALTER TABLE system_roles DROP COLUMN special'), // there should not be any special roles in existing installations
        
        self::sql('ALTER TABLE project_roles ADD type ENUM (\'normal\', \'owner\') NOT NULL DEFAULT \'normal\' AFTER role_id'),
        self::sql('UPDATE project_roles SET type = \'owner\' WHERE special = TRUE'),
        self::sql('ALTER TABLE project_roles DROP COLUMN special')
    ];
  }
}

?>
