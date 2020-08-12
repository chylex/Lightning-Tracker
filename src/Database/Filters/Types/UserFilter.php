<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractFilter;
use Database\Filters\Conditions\FieldLike;
use Database\Filters\Conditions\FieldOneOf;
use Database\Filters\General\Filtering;
use Database\Filters\General\Sorting;
use Database\Filters\IWhereCondition;

final class UserFilter extends AbstractFilter{
  public static function empty(): self{
    return new self(false);
  }
  
  private bool $allow_filtering_email;
  
  public function __construct(bool $allow_filtering_email){
    $this->allow_filtering_email = $allow_filtering_email;
  }
  
  protected function getFilteringColumns(): array{
    return [
        'name'  => Filtering::TYPE_TEXT,
        'email' => $this->allow_filtering_email ? Filtering::TYPE_TEXT : Filtering::TYPE_PROHIBITED,
        'role'  => Filtering::TYPE_MULTISELECT
    ];
  }
  
  protected function getFilterWhereCondition(string $field, $value): ?IWhereCondition{
    switch($field){
      case 'name':
      case 'email':
        return new FieldLike($field, $value);
      
      case 'role':
        return new FieldOneOf('title', array_map(fn($v): ?string => empty($v) ? null : $v, $value), 'sr');
      
      default:
        return null;
    }
  }
  
  protected function getSortingColumns(): array{
    return [
        'name',
        'role_title',
        'date_registered'
    ];
  }
  
  protected function getDefaultOrderByColumns(): array{
    return [
        'date_registered' => Sorting::SQL_ASC
    ];
  }
}

?>
