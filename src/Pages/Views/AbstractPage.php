<?php
declare(strict_types = 1);

namespace Pages\Views;

use Pages\Components\Navigation\NavigationComponent;
use Pages\IModel;
use Pages\IViewable;
use Routing\Request;

abstract class AbstractPage implements IViewable{
  protected const LAYOUT_FULL = 'full';
  protected const LAYOUT_CONDENSED = 'condensed';
  protected const LAYOUT_COMPACT = 'compact';
  protected const LAYOUT_MINIMAL = 'minimal';
  
  protected static final function breadcrumb(Request $req, string $link, string $title = 'Back'): string{
    return '<a href="'.$req->getBasePath()->encoded().$link.'">'.$title.'</a> <span class="breadcrumb-arrows">&raquo;</span> ';
  }
  
  private IModel $model;
  
  public function __construct(IModel $model){
    $model->ensureLoaded();
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return 'Lightning Tracker - '.$this->getSubtitle();
  }
  
  protected function getSubtitle(): string{
    return $this->getHeading();
  }
  
  protected abstract function getHeading(): string;
  protected abstract function getLayout(): string;
  
  protected function echoPageHead(): void{
  }
  
  protected abstract function echoPageBody(): void;
  
  /** @noinspection HtmlMissingClosingTag */
  public final function echoBody(): void{
    $base_url = BASE_URL_ENC;
    $v = TRACKER_RESOURCE_VERSION;
    
    $title = $this->getTitle();
    $heading = $this->getHeading();
    $layout = $this->getLayout();
    
    if (empty($heading)){
      $layout .= ' pad-top';
    }
    else{
      $heading = '<h2>'.$heading.'</h2>';
    }
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>$title</title>
    
    <base href="$base_url/">
    <link rel="icon" type="image/png" href="~resources/img/favicon.png?v=$v">
    <link rel="stylesheet" type="text/css" href="~resources/css/main.css?v=$v">
    <link rel="stylesheet" type="text/css" href="~resources/css/icons.css?v=$v">
HTML;
    
    NavigationComponent::echoHead();
    $this->echoPageHead();
    
    echo <<<HTML
  </head>
  <body>
HTML;
    
    $this->model->getNav()->echoBody();
    
    echo <<<HTML
    <main id="page-content" class="$layout">
      $heading
HTML;
    
    $this->echoPageBody();
    
    echo <<<HTML
    </main>
  </body>
</html>
HTML;
  }
}

?>
