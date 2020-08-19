<?php
declare(strict_types = 1);

namespace Routing;

use Pages\Components\Forms\FormComponent;

final class Request{
  public static function empty(): self{
    return new Request('', '', []);
  }
  
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
    return new UrlString(mb_substr($this->full_path, mb_strlen($this->base_path)));
  }
  
  public function getParam(string $name): ?string{
    return $this->params[$name] ?? null;
  }
  
  public function getData(): array{
    return $this->data;
  }
  
  public function getAction(): ?string{
    return $this->data[FormComponent::ACTION_KEY] ?? null;
  }
}

?>
