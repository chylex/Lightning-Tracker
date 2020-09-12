<?php
declare(strict_types = 1);

namespace Update\Migrations;

use Update\AbstractMigrationProcess;

final class Migration8 extends AbstractMigrationProcess{
  public function getTasks(): array{
    return [
        self::sql('ALTER TABLE system_role_permissions MODIFY permission ENUM (\'settings\', \'projects.list\', \'projects.list.all\', \'projects.create\', \'projects.manage\', \'users.list\', \'users.see.emails\', \'users.create\', \'users.manage\') NOT NULL'),
    ];
  }
}

?>
