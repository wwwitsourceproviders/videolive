<?php

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('memory_limit','1000M');
ini_set('display_startup_errors', TRUE);
header("Cache-Control: no-cache, must-revalidate");
session_cache_limiter(false);
//ini_set('session.cookie_secure', 1);
//ini_set('session.save_path','/home/c5e947wfpzdk/sessiontmp');
session_start();
require_once('application/config/config.php');
require_once('application/registry/uri.class.php');
$uri = URI::getInstance();
$uri->setBase($config_base);
require_once('application/channel/application.php');
?>