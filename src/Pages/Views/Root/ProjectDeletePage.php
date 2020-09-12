<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Root\ProjectDeleteModel;
use Pages\Views\AbstractPage;
use Routing\Request;

class ProjectDeletePage extends AbstractPage{
  private ProjectDeleteModel $model;
  
  public function __construct(ProjectDeleteModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getSubtitle(): string{
    return 'Projects';
  }
  
  protected function getHeading(): string{
    return self::breadcrumb(Request::empty(), '').'Delete Project - '.$this->model->getProject()->getNameSafe();
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    $stats = $this->model->calculateDeletionStats();
    $stats_str = implode('', array_map(static fn($v): string => '<li>'.$v[0].' '.($v[0] === 1 ? $v[1] : $v[2]).'</li>', $stats));
    
    echo <<<HTML
<h3>Confirm</h3>
<article>
  <p>Deleting a project cannot be reversed. If you proceed, you will lose:</p>
  <ul>
    $stats_str
  </ul>
  <p>To confirm deletion, please enter the project name.</p>
  <div class="max-width-250">
HTML;
    
    $this->model->getConfirmationForm()->echoBody();
    
    echo <<<HTML
  </div>
</article>
HTML;
  }
}

?>
