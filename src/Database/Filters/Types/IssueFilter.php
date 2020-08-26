<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractProjectIdFilter;
use Database\Filters\Conditions\FieldLike;
use Database\Filters\Conditions\FieldOneOf;
use Database\Filters\Conditions\FieldOneOfNullable;
use Database\Filters\Field;
use Database\Filters\General\Filtering;
use Database\Filters\General\Sorting;
use Database\Filters\IWhereCondition;

final class IssueFilter extends AbstractProjectIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  protected function getFilteringColumns(): array{
    return [
        'title'     => Filtering::TYPE_TEXT,
        'type'      => Filtering::TYPE_MULTISELECT,
        'priority'  => Filtering::TYPE_MULTISELECT,
        'scale'     => Filtering::TYPE_MULTISELECT,
        'status'    => Filtering::TYPE_MULTISELECT,
        'milestone' => Filtering::TYPE_MULTISELECT,
        'author'    => Filtering::TYPE_MULTISELECT,
        'assignee'  => Filtering::TYPE_MULTISELECT
    ];
  }
  
  protected function getFilterWhereCondition(string $field, $value): ?IWhereCondition{
    switch($field){
      case 'title':
        return new FieldLike($field, $value, 'i');
      
      case 'type':
      case 'priority':
      case 'scale':
      case 'status':
        return new FieldOneOf($field, $value, 'i');
      
      case 'milestone':
        return new FieldOneOfNullable($field.'_id', $value, 'm');
      
      case 'author':
      case 'assignee':
        return new FieldOneOfNullable($field.'_id', $value, 'i');
      
      default:
        return null;
    }
  }
  
  protected function getSortingFields(): array{
    return [
        new Field('id'),
        new Field('title', 'i'),
        new Field('priority', 'i'),
        new Field('scale', 'i'),
        new Field('progress', 'i'),
        new Field('date_updated', 'i')
    ];
  }
  
  protected function getDefaultSortingRuleList(): array{
    return [
        (new Field('id'))->sortRule(Sorting::SQL_DESC)
    ];
  }
}

?>
