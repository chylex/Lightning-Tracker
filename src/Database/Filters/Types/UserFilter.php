<?php
declare(strict_types = 1);

namespace Database\Filters\Types;

use Database\Filters\AbstractFilter;
use PDOStatement;
use function Database\bind;

final class UserFilter extends AbstractFilter{
  public static function empty(): self{
    return new self();
  }
  
  private ?string $name = null;
  private ?string $email = null;
  
  public function name(string $name): self{
    $this->name = $name;
    return $this;
  }
  
  public function email(string $email): self{
    $this->email = $email;
    return $this;
  }
  
  protected function getWhereColumns(): array{
    return [
        'name'  => $this->name === null ? null : self::OP_LIKE,
        'email' => $this->email === null ? null : self::OP_LIKE
    ];
  }
  
  protected function getOrderByColumns(): array{
    return [
        'id' => self::ORDER_ASC
    ];
  }
  
  public function prepareStatement(PDOStatement $stmt): void{
    bind($stmt, 'name', $this->name);
    bind($stmt, 'email', $this->email);
  }
}

?>
