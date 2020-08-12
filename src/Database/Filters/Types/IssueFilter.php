<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;
use Database\Filters\Conditions\FieldLike;
use Database\Filters\Conditions\FieldOneOf;
use Database\Filters\General\Filtering;
use Database\Filters\General\Sorting;
use Database\Filters\IWhereCondition;

final class IssueFilter extends AbstractTrackerIdFilter{
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
        return new FieldLike($field, $value);
      
      case 'type':
      case 'priority':
      case 'scale':
      case 'status':
        return new FieldOneOf($field, $value);
      
      case 'milestone':
      case 'author':
      case 'assignee':
        return new FieldOneOf($field.'_id', $value);
      
      default:
        return null;
    }
  }
  
  protected function getSortingColumns(): array{
    return [
        'title',
        'priority',
        'scale',
        'progress'
    ];
  }
  
  protected function getDefaultOrderByColumns(): array{
    return [
        'issue_id' => Sorting::SQL_DESC
    ];
  }
}

?>
