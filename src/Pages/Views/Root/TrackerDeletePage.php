<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Components\Forms\FormComponent;
use Pages\Models\Root\TrackerDeleteModel;
use Pages\Views\AbstractPage;

class TrackerDeletePage extends AbstractPage{
  private TrackerDeleteModel $model;
  
  public function __construct(TrackerDeleteModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getTitle(): string{
    return 'Lightning Tracker - Delete Tracker';
  }
  
  protected function getHeading(): string{
    return 'Delete Tracker - '.$this->model->getTracker()->getNameSafe();
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  protected function echoPageHead(): void{
    FormComponent::echoHead();
  }
  
  /** @noinspection HtmlMissingClosingTag */
  protected function echoPageBody(): void{
    $stats = $this->model->getDeletionStats();
    $stats_str = implode('', array_map(fn($v): string => '<li>'.$v[0].' '.($v[0] === 1 ? $v[1] : $v[2]).'</li>', $stats));
    
    echo <<<HTML
<h3>Confirm</h3>
<article>
  <p>Deleting a tracker cannot be reversed. If you proceed, you will lose:</p>
  <ul>
    $stats_str
  </ul>
  <p>To confirm deletion, please enter the tracker name.</p>
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
