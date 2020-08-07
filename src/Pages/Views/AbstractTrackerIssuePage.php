<?php
declare(strict_types = 1);

namespace Pages\Views;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Models\BasicTrackerPageModel;

abstract class AbstractTrackerIssuePage extends AbstractTrackerPage{
  private BasicTrackerPageModel $model;
  
  public function __construct(BasicTrackerPageModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    $back_url = $this->getHeadingBackUrl();
    
    return <<<HTML
<a href="$back_url">Back</a> <span class="breadcrumb-arrows">&raquo;</span>
HTML;
  }
  
  protected abstract function getHeadingBackUrl(): string;
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected final function echoPageHead(): void{
    FormComponent::echoHead();
    SidemenuComponent::echoHead();
    ProgressBarComponent::echoHead();
    DateTimeComponent::echoHead();
    
    echo <<<HTML
<link rel="stylesheet" type="text/css" href="~resources/css/issues.css">
<link rel="stylesheet" type="text/css" href="~resources/css/issuedetail.css">
HTML;
  }
  
  protected final function echoIssueMissing(): void{
    echo <<<HTML
<h3>Issue Error</h3>
<article>
  <p>Issue was not found.</p>
</article>
HTML;
  }
}

?>