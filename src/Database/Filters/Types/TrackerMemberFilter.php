<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;
use Database\Filters\Conditions\FieldLike;
use Database\Filters\Conditions\FieldOneOf;
use Database\Filters\General\Filtering;
use Database\Filters\General\Sorting;
use Database\Filters\IWhereCondition;

final class TrackerMemberFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  private ?string $role_title_table_name = null;
  private ?string $role_title_field_name = null;
  
  public function setRoleTitleColumn(?string $role_title_table_name, string $role_title_field_name): self{
    $this->role_title_table_name = $role_title_table_name;
    $this->role_title_field_name = $role_title_field_name;
    return $this;
  }
  
  protected function getFilteringColumns(): array{
    return [
        'name' => Filtering::TYPE_TEXT,
        'role' => Filtering::TYPE_MULTISELECT
    ];
  }
  
  protected function getFilterWhereCondition(string $field, $value): ?IWhereCondition{
    switch($field){
      case 'name':
        return new FieldLike($field, $value, 'u');
      
      case 'role':
        return new FieldOneOf($this->role_title_field_name, array_map(fn($v): ?string => empty($v) ? null : $v, $value), $this->role_title_table_name);
      
      default:
        return null;
    }
  }
  
  protected function getSortingColumns(): array{
    return [
        'name',
        'role_title'
    ];
  }
  
  protected function getDefaultOrderByColumns(): array{
    return [
        'tr.special' => Sorting::SQL_DESC,
        'role_order' => Sorting::SQL_ASC,
        'user_id'    => Sorting::SQL_DESC
    ];
  }
}

?>
