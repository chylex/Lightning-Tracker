<?php
declare(strict_types = 1);

namespace Update\Migrations;

use Update\AbstractMigrationProcess;

final class Migration7 extends AbstractMigrationProcess{
  /** @noinspection SqlWithoutWhere */
  public function getTasks(): array{
    return [
        self::sql('ALTER TABLE system_roles ADD ordering SMALLINT NOT NULL AFTER title'),
        self::sql('UPDATE system_roles SET ordering = id'), // there should not be any special roles in existing installations
        self::sql('ALTER TABLE project_roles MODIFY ordering SMALLINT NOT NULL'),
        
        self::sql('ALTER TABLE project_members DROP FOREIGN KEY fk__project_member__role'),
        self::sql('ALTER TABLE project_role_permissions DROP FOREIGN KEY fk__project_role_permission__role'),
        
        self::sql('ALTER TABLE project_roles MODIFY role_id SMALLINT NOT NULL'),
        self::sql('ALTER TABLE project_members MODIFY role_id SMALLINT NULL'),
        self::sql('ALTER TABLE project_role_permissions MODIFY role_id SMALLINT NOT NULL'),
        
        self::sql('ALTER TABLE project_members ADD CONSTRAINT fk__project_member__role FOREIGN KEY (`role_id`, `project_id`) REFERENCES `project_roles` (`role_id`, `project_id`) ON UPDATE CASCADE ON DELETE RESTRICT'),
        self::sql('ALTER TABLE project_role_permissions ADD CONSTRAINT fk__project_role_permission__role FOREIGN KEY (`role_id`, `project_id`) REFERENCES `project_roles` (`role_id`, `project_id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        
        self::sql('ALTER TABLE projects ADD description TEXT NOT NULL AFTER url')
    ];
  }
}

?>
