<?php
declare(strict_types = 1);

namespace Routing;

final class Request{
  public static function prepareSession(array $data): void{
    session_start();
    
    foreach($data as $key => $value){
      $_SESSION[$key] = $value;
    }
  }
  
  private string $full_path;
  private string $base_path;
  private array $params;
  private array $data;
  
  public function __construct(string $full_path, string $base_path, array $params){
    $this->full_path = $full_path;
    $this->base_path = $base_path;
    $this->params = $params;
    
    if (empty($_POST)){
      session_start();
      $this->data = $_SESSION;
      session_destroy();
    }
    else{
      $this->data = $_POST;
    }
  }
  
  public function getFullPath(): UrlString{
    return new UrlString($this->full_path);
  }
  
  public function getBasePath(): UrlString{
    return new UrlString($this->base_path);
  }
  
  public function getRelativePath(): UrlString{
    return new UrlString(substr($this->full_path, strlen($this->base_path)));
  }
  
  public function getParam(string $name): ?string{
    return $this->params[$name] ?? null;
  }
  
  public function getData(): array{
    return $this->data;
  }
  
  /**
   * @param string $key
   * @param mixed $new_value
   * @return string
   */
  public function pathWithGet(string $key, $new_value): string{
    $data = $_GET;
    
    if ($new_value === null){
      unset($data[$key]);
    }
    else{
      $data[$key] = $new_value;
    }
    
    $query = http_build_query($data);
    
    if (!empty($query)){
      $query = '/?'.$query;
    }
    
    return $this->getFullPath()->encoded().$query;
  }
}

?>
