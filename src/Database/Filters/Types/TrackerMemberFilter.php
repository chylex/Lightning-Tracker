<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;
use Database\Filters\Conditions\FieldLike;
use Database\Filters\Conditions\FieldOneOfNullable;
use Database\Filters\Field;
use Database\Filters\General\Filtering;
use Database\Filters\General\Sorting;
use Database\Filters\IWhereCondition;

final class TrackerMemberFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
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
        return new FieldOneOfNullable('title', $value, 'tr');
      
      default:
        return null;
    }
  }
  
  protected function getSortingFields(): array{
    return [
        new Field('name', 'u'),
        new Field('role_title')
    ];
  }
  
  protected function getDefaultSortingRuleList(): array{
    return [
        (new Field('special', 'tr'))->sortRule(Sorting::SQL_DESC),
        (new Field('role_order'))->sortRule(Sorting::SQL_ASC),
        (new Field('user_id', 'tm'))->sortRule(Sorting::SQL_DESC)
    ];
  }
}

?>
