<?php

//Framework for a simple RESTful API implementation
require 'Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

//Grabbing the DB credentials to acces the necessary SQL tables
require 'config/config.php';
require 'classes/post-class.php';
require 'classes/requestsender-class.php';

$PostHandler = new PostAPI();
$RequestHandler = new requestSender();

//Creates a new mysqli connection to database
function dbConnect($host = DB_HOST, $user = DB_USER, $password = DB_PASSWORD, $dbname = DB_NAME) {
    $mysqli = new mysqli($host, $user, $password, $dbname);
    if ($mysqli->connect_error) {
        die("Connection failled:" . $mysqli->connect_error);
    } else {
        $mysqli->set_charset("utf8");
        return $mysqli;
    }
}

$app = new \Slim\Slim();

$app->group('/category', function () use ($app, $PostHandler, $RequestHandler) {

    $app->get('/:term_id', function($term_id) use ($app, $PostHandler, $RequestHandler) {
        $postArray = $PostHandler->getRecentPostsByCategory(TABLE_PREFIX, $term_id);
        $postListArray = $PostHandler->getPostListByID(TABLE_PREFIX, $postArray);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $postListArray);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->get ('/:term_id/:count', function($term_id,$count) use ($app, $PostHandler, $RequestHandler) {
        $postArray = $PostHandler->getRecentPostsByCategory(TABLE_PREFIX, $term_id, $count);
        $postListArray = $PostHandler->getPostListByID(TABLE_PREFIX, $postArray);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $postListArray);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->get ('/:term_id/:count/:index', function($term_id, $count, $index) use ($app, $PostHandler, $RequestHandler) {
        $postArray = $PostHandler->getRecentPostsByCategory(TABLE_PREFIX, $term_id, $count, $index);
        $postListArray = $PostHandler->getPostListByID(TABLE_PREFIX, $postArray);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $postListArray);
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/post', function() use ($app, $PostHandler, $RequestHandler) {

    $app->get('/:post_id', function($post_id) use ($app, $PostHandler, $RequestHandler) {
        $data = $PostHandler->getPostByID(TABLE_PREFIX, $post_id);
        $RequestHandler->sendJSONResponse($app, $data);
    });

});

$app->run();

?>
