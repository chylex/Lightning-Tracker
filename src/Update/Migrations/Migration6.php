<?php
declare(strict_types = 1);

namespace Update\Migrations;

use Data\UserId;
use PDO;
use Update\AbstractMigrationProcess;
use Update\AbstractMigrationTask;
use Update\Tasks\DropAllForeignKeysTask;

final class Migration6 extends AbstractMigrationProcess{
  public function getTasks(): array{
    /** @noinspection SqlResolve, SqlWithoutWhere */
    return [
        new DropAllForeignKeysTask(),
        
        self::sql('ALTER TABLE users ADD public_id CHAR(9) NOT NULL FIRST'),
        
        self::sql('ALTER TABLE issues CHANGE author_id author_id_old INT NULL'),
        self::sql('ALTER TABLE issues CHANGE assignee_id assignee_id_old INT NULL'),
        self::sql('ALTER TABLE project_members CHANGE user_id user_id_old INT NOT NULL'),
        self::sql('ALTER TABLE projects CHANGE owner_id owner_id_old INT NOT NULL'),
        self::sql('ALTER TABLE project_user_settings CHANGE user_id user_id_old INT NOT NULL'),
        self::sql('ALTER TABLE user_logins CHANGE id id_old INT NOT NULL'),
        
        self::sql('ALTER TABLE project_members DROP PRIMARY KEY'),
        self::sql('ALTER TABLE project_user_settings DROP PRIMARY KEY'),
        self::sql('ALTER TABLE user_logins DROP PRIMARY KEY'),
        
        self::sql('ALTER TABLE issues ADD author_id CHAR(9) NULL AFTER author_id_old'),
        self::sql('ALTER TABLE issues ADD assignee_id CHAR(9) NULL AFTER assignee_id_old'),
        self::sql('ALTER TABLE project_members ADD user_id CHAR(9) NOT NULL AFTER user_id_old'),
        self::sql('ALTER TABLE projects ADD owner_id CHAR(9) NOT NULL AFTER owner_id_old'),
        self::sql('ALTER TABLE project_user_settings ADD user_id CHAR(9) NOT NULL AFTER user_id_old'),
        self::sql('ALTER TABLE user_logins ADD id CHAR(9) NOT NULL AFTER id_old'),
        
        new class extends AbstractMigrationTask{
          public function execute(PDO $db): void{
            $stmt = $db->query('SELECT id FROM users');
  
            while(($res = $stmt->fetchColumn()) !== false){
              /** @noinspection SqlResolve */
              $s2 = $db->prepare('UPDATE users SET public_id = ? WHERE id = ?');
              $s2->bindValue(1, UserId::generateNew());
              $s2->bindValue(2, (int)$res, PDO::PARAM_INT);
              $s2->execute();
            }
          }
        },

        self::sql('UPDATE issues SET author_id = (SELECT u.public_id FROM users u WHERE u.id = author_id_old)'),
        self::sql('UPDATE issues SET assignee_id = (SELECT u.public_id FROM users u WHERE u.id = assignee_id_old)'),
        self::sql('UPDATE project_members SET user_id = (SELECT u.public_id FROM users u WHERE u.id = user_id_old)'),
        self::sql('UPDATE projects SET owner_id = (SELECT u.public_id FROM users u WHERE u.id = owner_id_old)'),
        self::sql('UPDATE project_user_settings SET user_id = (SELECT u.public_id FROM users u WHERE u.id = user_id_old)'),
        self::sql('UPDATE user_logins SET id = (SELECT u.public_id FROM users u WHERE u.id = id_old)'),

        self::sql('ALTER TABLE users DROP id'),
        self::sql('ALTER TABLE users CHANGE public_id id CHAR(9) NOT NULL'),
        self::sql('ALTER TABLE users ADD PRIMARY KEY (id)'),

        self::sql('ALTER TABLE project_members ADD PRIMARY KEY (project_id, user_id)'),
        self::sql('ALTER TABLE project_user_settings ADD PRIMARY KEY (project_id, user_id)'),
        self::sql('ALTER TABLE user_logins ADD PRIMARY KEY (id, token)'),

        self::sql('ALTER TABLE issues ADD CONSTRAINT fk__issue__project FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE issues ADD CONSTRAINT fk__issue__author FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL'),
        self::sql('ALTER TABLE issues ADD CONSTRAINT fk__issue__assignee FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL'),
        self::sql('ALTER TABLE issues ADD CONSTRAINT fk__issue__milestone FOREIGN KEY (`milestone_id`, `project_id`) REFERENCES `milestones` (`milestone_id`, `project_id`) ON UPDATE CASCADE ON DELETE RESTRICT'),
        self::sql('ALTER TABLE issues ADD CONSTRAINT fk__issue__scale FOREIGN KEY (`scale`) REFERENCES `issue_weights` (`scale`) ON UPDATE RESTRICT ON DELETE RESTRICT'),
        self::sql('ALTER TABLE milestones ADD CONSTRAINT fk__milestone__project FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE project_members ADD CONSTRAINT fk__project_member__project FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE project_members ADD CONSTRAINT fk__project_member__user FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE project_members ADD CONSTRAINT fk__project_member__role FOREIGN KEY (`role_id`, `project_id`) REFERENCES `project_roles` (`role_id`, `project_id`) ON UPDATE CASCADE ON DELETE RESTRICT'),
        self::sql('ALTER TABLE project_role_permissions ADD CONSTRAINT fk__project_role_permission__role FOREIGN KEY (`role_id`, `project_id`) REFERENCES `project_roles` (`role_id`, `project_id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE project_roles ADD CONSTRAINT fk__project_role__project FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE ON DELETE CASCADE '),
        self::sql('ALTER TABLE projects ADD CONSTRAINT fk__project__owner FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT'),
        self::sql('ALTER TABLE project_user_settings ADD CONSTRAINT fk__project_user_setting__user FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE project_user_settings ADD CONSTRAINT fk__project_user_setting__active_milestone FOREIGN KEY (`active_milestone`, `project_id`) REFERENCES `milestones` (`milestone_id`, `project_id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE system_role_permissions ADD CONSTRAINT fk__system_role_permission__role FOREIGN KEY (`role_id`) REFERENCES `system_roles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE user_logins ADD CONSTRAINT fk__user_login__user FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE'),
        self::sql('ALTER TABLE users ADD CONSTRAINT fk__user__role FOREIGN KEY (`role_id`) REFERENCES `system_roles` (`id`) ON UPDATE CASCADE ON DELETE SET NULL'),

        self::sql('ALTER TABLE issues DROP author_id_old'),
        self::sql('ALTER TABLE issues DROP assignee_id_old'),
        self::sql('ALTER TABLE project_members DROP user_id_old'),
        self::sql('ALTER TABLE projects DROP owner_id_old'),
        self::sql('ALTER TABLE project_user_settings DROP user_id_old'),
        self::sql('ALTER TABLE user_logins DROP id_old')
    ];
  }
}

?>
