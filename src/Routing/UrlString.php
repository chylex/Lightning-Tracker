<?php
declare(strict_types = 1);

namespace Routing;

final class UrlString{
  private string $url;
  
  public function __construct(string $url){
    $this->url = $url;
  }
  
  public function raw(): string{
    return $this->url;
  }
  
  public function encoded(): string{
    return implode('/', array_map(static fn($v): string => rawurlencode($v), explode('/', $this->url)));
  }
}

?>
