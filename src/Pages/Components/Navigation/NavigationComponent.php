<?php
declare(strict_types = 1);

namespace Pages\Components\Navigation;

use Pages\Components\Text;
use Pages\IViewable;
use Routing\Request;
use Routing\UrlString;

final class NavigationComponent implements IViewable{
  public static function echoHead(): void{
    $v = TRACKER_RESOURCE_VERSION;
    
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/navigation.css?v=$v">
HTML;
  }
  
  private string $title;
  private string $home_url;
  private string $base_url;
  private string $active_path_normalized;
  
  /**
   * @var NavigationItem[]
   */
  private array $left = [];
  
  /**
   * @var NavigationItem[]
   */
  private array $right = [];
  
  public function __construct(string $title, string $home_url, UrlString $base_path, UrlString $relative_path){
    $this->title = $title;
    $this->home_url = $home_url;
    $this->base_url = rtrim($home_url.'/'.$base_path->encoded(), '/');
    $this->active_path_normalized = trim($relative_path->raw(), '/');
  }
  
  private function createNavItem(Text $title, string $url): NavigationItem{
    $item = new NavigationItem($title, $this->base_url.$url);
    
    $url_trim = trim($url, '/');
    
    if ((empty($url_trim) && empty($this->active_path_normalized)) || (!empty($url_trim) && mb_strpos($this->active_path_normalized, $url_trim) === 0)){
      $item = $item->active();
    }
    
    return $item;
  }
  
  public function addLeft(Text $title, string $url): void{
    $this->left[] = $this->createNavItem($title, $url);
  }
  
  public function addRight(Text $title, string $url): void{
    $this->right[] = $this->createNavItem($title, $url);
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $v = TRACKER_RESOURCE_VERSION;
    
    echo <<<HTML
<nav id="navigation">
  <header class="title">
    <a href="$this->home_url"><img src="~resources/img/logo.png?v=$v" aria-label="Lightning Tracker Homepage" alt="" width="36" height="48"></a>
    <h1><a href="$this->base_url">$this->title</a></h1>
  </header>
  
  <main class="left">
HTML;
    
    foreach($this->left as $item){
      $item->echoBody();
    }
    
    echo <<<HTML
  </main>
  
  <aside class="right">
HTML;
    
    foreach($this->right as $item){
      $item->echoBody();
    }
    
    echo <<<HTML
  </aside>
</nav>
HTML;
  }
}

?>
