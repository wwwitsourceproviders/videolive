<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('memory_limit','1000M');
ini_set('display_startup_errors', TRUE);
header("Cache-Control: no-cache, must-revalidate");
session_cache_limiter(false);
session_start();

$VERSION = 'v1';
require_once($VERSION.'/application/config/config.php');
require_once($VERSION.'/application/registry/uri.class.php');
$uri=URI::getInstance();
$uri->setBase($config_base);
require_once($VERSION.'/application/channel/application.php');
?>