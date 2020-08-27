<?php
declare(strict_types = 1);

namespace Session\Permissions;

use Session\AbstractPermissionList;

final class SystemPermissions extends AbstractPermissionList{
  public const MANAGE_SETTINGS = 'settings';
  
  public const LIST_VISIBLE_PROJECTS = 'projects.list';
  public const LIST_ALL_PROJECTS = 'projects.list.all';
  public const CREATE_PROJECT = 'projects.create';
  public const MANAGE_PROJECTS = 'projects.manage';
  
  public const LIST_USERS = 'users.list';
  public const SEE_USER_EMAILS = 'users.see.emails';
  public const CREATE_USER = 'users.create';
  public const MANAGE_USERS = 'users.manage';
  
  public static function permitAll(): self{
    return new self(true, []);
  }
  
  public static function permitList(array $perms): self{
    return new self(false, $perms);
  }
  
  private function __construct(bool $override, array $perms){
    parent::__construct($override, $perms);
  }
  
  protected function getType(): string{
    return 'system';
  }
}

?>
