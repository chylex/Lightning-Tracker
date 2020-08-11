<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractFilter;
use Database\Filters\General\Sorting;

final class UserFilter extends AbstractFilter{
  public static function empty(): self{
    return new self();
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
