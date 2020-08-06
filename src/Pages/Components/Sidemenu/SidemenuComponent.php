<?php
declare(strict_types = 1);

namespace Pages\Components\Sidemenu;

use Pages\Components\Sidemenu\Elements\SidemenuActionButton;
use Pages\Components\Sidemenu\Elements\SidemenuItem;
use Pages\Components\Text;
use Pages\IViewable;
use Routing\Request;

final class SidemenuComponent implements IViewable{
  public static function echoHead(): void{
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/sidemenu.css">
HTML;
  }
  
  private string $base_url;
  private string $active_url_normalized;
  private ?Text $title = null;
  
  public function __construct(string $home_url, Request $req){
    $this->base_url = rtrim($home_url.'/'.$req->getBasePath()->encoded(), '/');
    $this->active_url_normalized = trim($req->getRelativePath()->raw(), '/');
  }
  
  /**
   * @var IViewable[]
   */
  private array $items = [];
  
  public function setTitle(Text $title): SidemenuComponent{
    $this->title = $title;
    return $this;
  }
  
  public function addLink(Text $title, string $url): void{
    $item = new SidemenuItem($title, $this->base_url.$url);
    
    $url_trim = ltrim($url, '/');
    
    if ((empty($url_trim) && empty($this->active_url_normalized)) || (!empty($url_trim) && $this->active_url_normalized === $url_trim)){
      $item = $item->active();
    }
    
    $this->items[] = $item;
  }
  
  public function addActionButton(Text $title, string $action): void{
    $this->items[] = new SidemenuActionButton($title, $action);
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    if (empty($this->items)){
      return;
    }
    
    if ($this->title !== null){
      echo '<h3>';
      $this->title->echoBody();
      echo '</h3>';
    }
    
    echo <<<HTML
<nav class="sidemenu">
  <ul>
HTML;
    
    foreach($this->items as $item){
      $item->echoBody();
    }
    
    echo <<<HTML
  </ul>
</nav>
HTML;
  }
}

?>
