<?php
declare(strict_types = 1);

namespace Session\Permissions;

use Session\AbstractPermissionList;

final class TrackerPermissions extends AbstractPermissionList{
  public const MANAGE_SETTINGS = 'settings';
  
  public const LIST_MEMBERS = 'members.list';
  public const MANAGE_MEMBERS = 'members.manage';
  
  public const MANAGE_MILESTONES = 'milestones.manage';
  
  public const CREATE_ISSUE = 'issues.create';
  public const MODIFY_ALL_ISSUE_FIELDS = 'issues.fields.all';
  public const EDIT_ALL_ISSUES = 'issues.edit.all';
  public const DELETE_ALL_ISSUES = 'issues.delete.all';
  
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
    return 'tracker';
  }
}

?>
