<?php
declare(strict_types = 1);

namespace Routing;

use Exception;
use InvalidArgumentException;
use Pages\IController;
use Session\Session;

final class Router{
  private array $routes = [];
  
  public function add(string $path, string $controller_name): void{
    $components = explode('/', $path);
    $route = &$this->routes;
    
    $base_url_components = 0;
    $base_url_found = false;
    
    foreach($components as $component){
      if (empty($component)){
        continue;
      }
      
      if ($component === '&'){
        if ($base_url_found){
          throw new InvalidArgumentException("Route '$path' has multiple base URL indicators.");
        }
        
        $base_url_found = true;
        continue;
      }
      
      if (!$base_url_found){
        $base_url_components++;
      }
      
      if ($component[0] === ':'){
        $name = substr($component, 1);
        
        if (array_key_exists(':', $route)){
          if ($route[':']['name'] !== $name){
            throw new InvalidArgumentException("Route '$path' already has a parameter.");
          }
        }
        else{
          $route[':'] = [
              'name' => $name,
              'next' => []
          ];
        }
        
        $route = &$route[':']['next'];
        continue;
      }
      
      if (!array_key_exists($component, $route)){
        $route[$component] = [];
      }
      
      $route = &$route[$component];
    }
    
    if (array_key_exists('/', $route)){
      throw new InvalidArgumentException("Route '$path' already exists.");
    }
    
    if (!$base_url_found){
      throw new InvalidArgumentException("Route '$path' is missing base URL indicator.");
    }
    
    $route['/'] = [
        'ctrl' => $controller_name,
        'base' => $base_url_components
    ];
  }
  
  /**
   * @param string $path
   * @throws RouterException
   */
  public function route(string $path): void{
    $components = array_filter(explode('/', $path), fn($component): bool => !empty($component));
    $path = implode('/', $components);
    
    $route = &$this->routes;
    $params = [];
    
    foreach($components as $component){
      if (empty($component)){
        continue;
      }
      
      if (array_key_exists($component, $route)){
        $route = &$route[$component];
        continue;
      }
      
      if (array_key_exists(':', $route)){
        $info = &$route[':'];
        
        $params[$info['name']] = $component;
        $route = &$info['next'];
        continue;
      }
      
      throw new RouterException("Page '$path' does not exist.", RouterException::STATUS_NOT_FOUND);
    }
    
    if (!array_key_exists('/', $route)){
      throw new RouterException("Page '$path' does not exist.", RouterException::STATUS_NOT_FOUND);
    }
    
    $route_info = $route['/'];
    
    $controller_name = $route_info['ctrl'];
    $base_path = implode('/', array_slice($components, 0, $route_info['base']));
    
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__.'/../Pages/Controllers/'.$controller_name.'.php';
    $controller_name = strtr($controller_name, '/', '\\');
    $controller_class = "\\Pages\\Controllers\\$controller_name";
    
    /** @var IController $controller */
    $controller = new $controller_class();
    $req = new Request($path, $base_path, $params);
    
    try{
      $action = $controller->run($req, Session::get());
      $action->execute();
    }catch(Exception $e){
      throw new RouterException($e->getMessage(), RouterException::STATUS_SERVER_ERROR, $e);
    }
  }
}

?>
