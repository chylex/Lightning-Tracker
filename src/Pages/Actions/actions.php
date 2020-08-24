<?php
declare(strict_types = 1);

namespace Pages\Actions;

use Pages\Views\AbstractPage;

function view(AbstractPage $view): ViewAction{
  return new ViewAction($view);
}

function redirect(string $url): RedirectAction{
  return new RedirectAction($url);
}

function reload(): RedirectAction{
  return new RedirectAction($_SERVER['REQUEST_URI']);
}

function json(array $data): JsonAction{
  return new JsonAction($data);
}

?>
