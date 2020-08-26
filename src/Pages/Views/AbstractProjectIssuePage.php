<?php
declare(strict_types = 1);

namespace Pages\Views;

use Pages\Components\DateTimeComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\ProgressBarComponent;
use Pages\Components\Sidemenu\SidemenuComponent;
use Pages\Components\SplitComponent;
use Pages\Models\BasicProjectPageModel;

abstract class AbstractProjectIssuePage extends AbstractProjectPage{
  private BasicProjectPageModel $model;
  
  public function __construct(BasicProjectPageModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return self::breadcrumb($this->model->getReq(), $this->getHeadingBackUrl());
  }
  
  protected abstract function getHeadingBackUrl(): string;
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    SplitComponent::echoHead();
    FormComponent::echoHead();
    SidemenuComponent::echoHead();
    ProgressBarComponent::echoHead();
    DateTimeComponent::echoHead();
    
    if (DEBUG){
      echo '<link rel="stylesheet" type="text/css" href="~resources/css/issues.css?v='.TRACKER_RESOURCE_VERSION.'">';
    }
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
