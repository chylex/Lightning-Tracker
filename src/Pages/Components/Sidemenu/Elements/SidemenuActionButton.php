<?php
declare(strict_types = 1);

namespace Pages\Components\Sidemenu\Elements;

use Pages\Components\Forms\FormComponent;
use Pages\Components\Text;
use Pages\IViewable;

final class SidemenuActionButton implements IViewable{
  private Text $title;
  private string $action;
  
  public function __construct(Text $title, string $action){
    $this->title = $title;
    $this->action = $action;
  }
  
  /** @noinspection HtmlMissingClosingTag */
  public function echoBody(): void{
    $action_key = FormComponent::ACTION_KEY;
    
    echo <<<HTML
<li>
  <form action="" method="post">
    <input type="hidden" name="$action_key" value="$this->action">
    <button>
HTML;
    
    $this->title->echoBody();
    
    echo <<<HTML
    </button>
  </form>
</li>
HTML;
  }
}

?>
