<?php
declare(strict_types = 1);

if (version_compare(PHP_VERSION, '7.4', '<')){
  die('Lightning Tracker requires PHP 7.4 or newer.');
}

define('TRACKER_VERSION', '0.1');

setlocale(LC_ALL, 'C');
date_default_timezone_set('UTC');
header_remove('x-powered-by');

// Bootstrap

spl_autoload_extensions('.php');
spl_autoload_register();

require_once 'config.php';
?>
