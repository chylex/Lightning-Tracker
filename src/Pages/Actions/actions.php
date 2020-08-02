<?php
declare(strict_types = 1);

namespace Pages\Actions;

use Pages\Views\AbstractPage;

function view(AbstractPage $view): ViewAction{
  return new ViewAction($view);
}

function redirect(array $components): RedirectAction{
  return new RedirectAction(implode('/', array_filter($components, fn($component): bool => !empty($component))));
}

function reload(): RedirectAction{
  return new RedirectAction($_SERVER['REQUEST_URI']);
}

?>
