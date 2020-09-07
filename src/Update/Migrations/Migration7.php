<?php
declare(strict_types = 1);

namespace Update\Migrations;

use Update\AbstractMigrationProcess;

final class Migration7 extends AbstractMigrationProcess{
  /** @noinspection SqlWithoutWhere */
  public function getTasks(): array{
    return [
        self::sql('ALTER TABLE system_roles ADD ordering SMALLINT NOT NULL AFTER title'),
        self::sql('UPDATE system_roles SET ordering = id') // there should not be any special roles in existing installations
    ];
  }
}

?>
