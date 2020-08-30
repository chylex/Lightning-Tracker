<?php
declare(strict_types = 1);

namespace Session;

abstract class AbstractPermissionList{
  /** @noinspection PhpUnused */
  public abstract static function permitAll(): self;
  
  /** @noinspection PhpUnused */
  public abstract static function permitList(array $perms): self;
  
  private bool $override;
  
  /**
   * @var string[]
   */
  private array $perms;
  
  protected function __construct(bool $override, array $perms){
    $this->override = $override;
    $this->perms = $perms;
  }
  
  protected abstract function getType(): string;
  
  public final function check(string $permission): bool{
    return $this->override || in_array($permission, $this->perms, true);
  }
  
  public final function require(string $permission): bool{
    if ($this->check($permission)){
      return true;
    }
    else{
      throw new PermissionException($this->getType().':'.$permission);
    }
  }
}

?>
