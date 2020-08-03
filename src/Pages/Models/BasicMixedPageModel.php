<?php
declare(strict_types = 1);

namespace Pages\Models;

use Routing\Request;

class BasicMixedPageModel extends AbstractWrapperModel{
  public function __construct(Request $req){
    parent::__construct(new BasicRootPageModel($req)); // TODO
  }
}

?>
