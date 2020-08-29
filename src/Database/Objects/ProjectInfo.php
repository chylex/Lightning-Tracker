<?php
declare(strict_types = 1);

namespace Database\Objects;

use Data\UserId;

final class ProjectInfo{
  private int $id;
  private string $name;
  private string $url;
  private string $owner_id;
  
  public function __construct(int $id, string $name, string $url, string $owner_id){
    $this->id = $id;
    $this->name = $name;
    $this->url = $url;
    $this->owner_id = $owner_id;
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
  
  public function getOwnerId(): UserId{
    return UserId::fromRaw($this->owner_id);
  }
}

?>
