<?php
declare(strict_types = 1);

namespace Pages\Controllers;

use Pages\IAction;
use Routing\Request;
use Session\Session;

interface IControlHandler{
  public function run(Request $req, Session $sess): ?IAction;
}

?>
