<?php
declare(strict_types = 1);

namespace Session\Permissions;

use Session\AbstractPermissionList;

final class SystemPermissions extends AbstractPermissionList{
  // TODO rename & reconsider permissions when adding system role editing
  
  public const LIST_PUBLIC_TRACKERS = 'trackers.list';
  public const LIST_ALL_TRACKERS = 'trackers.list.hidden';
  public const CREATE_TRACKER = 'trackers.add';
  public const MANAGE_TRACKERS = 'trackers.edit';
  
  public const LIST_USERS = 'users.list';
  public const LIST_USER_EMAILS = 'users.list.email';
  public const CREATE_USER = 'users.add';
  public const MANAGE_USERS = 'users.edit';
  
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
