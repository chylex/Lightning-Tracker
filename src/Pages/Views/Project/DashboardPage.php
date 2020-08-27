<?php
declare(strict_types = 1);

namespace Pages\Views\Project;

use Pages\Views\AbstractProjectPage;

class DashboardPage extends AbstractProjectPage{
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
