<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Components\DashboardWidgetComponent;
use Pages\Components\Forms\FormComponent;
use Pages\Components\Markup\LightMarkComponent;
use Pages\Models\Project\DashboardModel;
use Pages\Views\AbstractProjectPage;

class DashboardPage extends AbstractProjectPage{
  private DashboardModel $model;
  
  public function __construct(DashboardModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return '';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_FULL;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
    LightMarkComponent::echoHead();
    
    if (DEBUG){
      echo '<link rel="stylesheet" type="text/css" href="~resources/css/dashboard.css?v='.TRACKER_RESOURCE_VERSION.'">';
    }
  }
  
  protected function echoPageBody(): void{
    /** @var DashboardWidgetComponent[] $widgets */
    $widgets = [$this->model->getProjectDescription()];
    $widgets = array_filter($widgets, fn($v): bool => $v !== null);
    
    echo '<div class="dashboard-panels">';
    
    foreach($widgets as $widget){
      $widget->echoBody();
    }
    
    echo '</div>';
  }
}

?>
