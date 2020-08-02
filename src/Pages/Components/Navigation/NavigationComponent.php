<?php
declare(strict_types = 1);

namespace Pages\Components\Navigation;

use Pages\Components\Text;
use Pages\IViewable;
use Routing\Request;

final class NavigationComponent implements IViewable{
  public static function echoHead(): void{
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/navigation.css">
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
  
  public function __construct(string $title, string $home_url, Request $req){
    $this->title = $title;
    $this->home_url = $home_url;
    $this->base_url = rtrim($home_url.'/'.$req->getBasePath()->encoded(), '/');
    $this->active_path_normalized = trim($req->getRelativePath()->raw(), '/');
  }
  
  private function createNavItem(Text $title, string $url): NavigationItem{
    $item = new NavigationItem($title, $this->base_url.$url);
    
    $url_trim = trim($url, '/');
    
    if ((empty($url_trim) && empty($this->active_path_normalized)) || (!empty($url_trim) && strpos($this->active_path_normalized, $url_trim) === 0)){
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
    echo <<<HTML
<nav id="navigation">
  <header class="title">
    <a href="$this->home_url"><img src="~resources/img/logo.png" alt="" width="36" height="48"></a>
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
