<?php
use Slim\App;

////////////////////////////////////////////////////////////////
//The registry will hold my mysqli, uri,api
require_once(BASE . '/application/registry/registry.class.php');
//Load external classes

require_once(BASE . '/application/vendor/autoload.php');
require_once(BASE . '/application/registry/uri.class.php');
require_once(BASE . '/application/controller/default.class.php');
require_once(BASE . '/application/libraries/UploadHandler.php');



$uri = URI::getInstance();
//Slim
$app = new App();
$container = $app->getContainer();
//The slim library will hold a reference to the registry itself
$container['register'] = new Registry();
header_remove("X-Powered-By");
header_remove("Server");
header_remove("X-Frame-Options");
/*
 if ($origin == 'https://www.podiumacademicmanagement.com' || $origin == 'https://podiumacademicmanagement.com') {
  //header("Access-Control-Allow-Origin: $origin");
  //exit();
  }*/
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") {
  if ((strpos($_SERVER['HTTP_HOST'], 'www.') === true)) {
  $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  } else {
  $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  }
  header('HTTP/1.1 301 Moved Permanently');
  header('Location: ' . $redirect);
  exit();
  } 
set_time_limit(0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Token,Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers,Content-Disposition,Content-Range");
date_default_timezone_set('UTC');
$C = new Controller($app);
