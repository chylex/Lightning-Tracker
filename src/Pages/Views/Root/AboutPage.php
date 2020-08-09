<?php
declare(strict_types = 1);

namespace Pages\Views\Root;

use Pages\Models\BasicRootPageModel;
use Pages\Views\AbstractPage;

class AboutPage extends AbstractPage{
  public function __construct(BasicRootPageModel $model){
    parent::__construct($model);
  }
  
  protected function getTitle(): string{
    return 'Lightning Tracker - About';
  }
  
  protected function getHeading(): string{
    return 'About Lightning Tracker '.TRACKER_PUBLIC_VERSION;
  }
  
  protected function getLayout(): string{
    return self::LAYOUT_CONDENSED;
  }
  
  protected function echoPageBody(): void{
    echo <<<HTML
<h3>General</h3>
<article>
  <p>
    Lightning Tracker is developed by <a href="https://chylex.com" rel="noopener">chylex</a>.
    The project is open-source and available on <a href="https://github.com/chylex/Lightning-Tracker" rel="noopener">GitHub</a>.
  </p>
  <ul>
    <li>Follow me on Twitter &mdash; <a href="https://twitter.com/chylexmc" rel="noopener">@chylexmc</a></li>
    <li>Support me on Ko-fi &mdash; <a href="https://ko-fi.com/chylex" rel="noopener">ko-fi.com/chylex</a></li>
    <li>Support me on Patreon &mdash; <a href="https://patreon.com/chylex" rel="noopener">patreon.com/chylex</a></li>
  </ul>
</article>

<h3>External Resources</h3>
<article>
  <ul>
    <li>Roboto font &mdash; <a href="https://fonts.google.com/specimen/Roboto" rel="noopener">https://fonts.google.com/specimen/Roboto</a>, licensed under <a href="http://www.apache.org/licenses/LICENSE-2.0" rel="noopener">Apache-2.0</a></li>
    <li>IcoMoon icon pack &mdash; <a href="https://icomoon.io" rel="noopener">https://icomoon.io</a>, licensed under <a href="https://creativecommons.org/licenses/by/4.0/" rel="noopener">CC BY 4.0</a></li>
  </ul>
</article>
HTML;
  }
}

?>
