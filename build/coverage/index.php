<?php
/** @noinspection PhpMissingStrictTypesDeclarationInspection */

require __DIR__.'/c3.php';

/** @noinspection ConstantCanBeUsedInspection */
if (version_compare(PHP_VERSION, '7.4', '<')){
  die('Lightning Tracker requires PHP 7.4 or newer.');
}

require __DIR__.'/bootstrap.php';
?>
