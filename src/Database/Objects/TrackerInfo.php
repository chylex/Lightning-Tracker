<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class TrackerInfo{
  private int $id;
  private string $name;
  private string $url;
  
  public function __construct(int $id, string $name, string $url){
    $this->id = $id;
    $this->name = $name;
    $this->url = $url;
  }
  
  public function getId(): int{
    return $this->id;
  }
  
  public function getName(): string{
    return $this->name;
  }
  
  public function getNameSafe(): string{
    return protect($this->name);
  }
  
  public function getUrl(): string{
    return $this->url;
  }
  
  public function getUrlSafe(): string{
    return protect($this->url);
  }
}

?>