<?php
declare(strict_types = 1);

namespace Pages\Components\Forms\Elements;

use Pages\IViewable;

final class FormSplitGroupEnd implements IViewable{
  public function echoBody(): void{
    echo '</div>';
  }
}

?>
