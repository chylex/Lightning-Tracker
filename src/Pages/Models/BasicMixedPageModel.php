<?php
declare(strict_types = 1);

namespace Pages\Models;

use Database\Objects\ProjectInfo;
use Routing\Request;

class BasicMixedPageModel extends AbstractWrapperModel{
  public function __construct(Request $req, ?ProjectInfo $project){
    parent::__construct(($project === null) ? new BasicRootPageModel($req) : new BasicProjectPageModel($req, $project));
  }
}

?>
