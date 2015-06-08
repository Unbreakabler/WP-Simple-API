<?php

require 'Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/hello/:name', function ($name) use ($app){
  $response = $app->response();
  $response->header('Access-Control-Allow-Origin', '*');
  $response->write(json_encode("Hello, $name"));
});

$app->run();

?>
