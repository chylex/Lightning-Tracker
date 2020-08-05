<?php
declare(strict_types = 1);

namespace Database\Objects;

use function Database\protect;

final class TrackerInfo{
  private int $id;
  private string $name;
  private string $url;
  private int $owner_id;
  
  public function __construct(int $id, string $name, string $url, int $owner_id){
    $this->id = $id;
    $this->name = $name;
    $this->url = $url;
    $this->owner_id = $owner_id;
  }
  
  public function getId(): int{
    return $this->id;
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
  
  public function getOwnerId(): int{
    return $this->owner_id;
  }
}

?>
