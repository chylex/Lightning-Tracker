<?php
declare(strict_types = 1);

namespace Database\Filters;

use PDOStatement;

interface IWhereCondition{
  public function getSql(): string;
  public function prepareStatement(PDOStatement $stmt): void;
}

?>
