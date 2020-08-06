<?php
declare(strict_types = 1);

namespace Pages\Views\Tracker;

use Pages\Models\BasicTrackerPageModel;
use Pages\Views\AbstractTrackerPage;

class DashboardPage extends AbstractTrackerPage{
  private BasicTrackerPageModel $model;
  
  public function __construct(BasicTrackerPageModel $model){
    parent::__construct($model);
    $this->model = $model;
  }
  
  protected function getHeading(): string{
    return 'Dashboard';
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  protected function echoPageBody(): void{
    echo <<<HTML
<h3>Issues</h3>
<article>
  <p>TODO</p>
</article>
HTML;
  }
}

?>
