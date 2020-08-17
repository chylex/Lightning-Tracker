<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractTrackerIdFilter;
use Database\Filters\Field;
use Database\Filters\General\Sorting;

final class MilestoneFilter extends AbstractTrackerIdFilter{
  public static function empty(): self{
    return new self();
  }
  
  protected function getSortingFields(): array{
    return [
        new Field('title', 'm'),
        new Field('progress'),
        new Field('date_updated')
    ];
  }
  
  protected function getDefaultSortingRuleList(): array{
    return [
        (new Field('ordering', 'm'))->sortRule(Sorting::SQL_ASC)
    ];
  }
}

?>
