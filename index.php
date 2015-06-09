<?php

//Framework for a simple RESTful API implementation
require 'Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

//Grabbing the DB credentials to acces the necessary SQL tables
require 'config/config.php';

//Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$result = $conn->query("SELECT name FROM ez_terms ORDER BY id ASC");
var_dump($conn);

if ($conn->connect_error) {
    die("Connection failled:" . $conn->connect_error);
}
echo "connected successfully!";

$app = new \Slim\Slim();

//Test http->get route, used for initial testing the connection between the app and server
$app->get('/hello/:name', function ($name) use ($app){
    $response = $app->response();
    $response->header('Access-Control-Allow-Origin', '*');
    $response->write(json_encode("Hello, $name"));
});

/*  News Article API
*
*   - Get list of recent articles from /articles/
*   - Get specific article from /articles/:id
*   - Get list of article categories from /articles/categories
*   - Get list of articles from a specific category from /articles/categories/:category
*   ? Get specific article by name from /articles/:name
*   ? Get list of articles on a certain date from /articles/:date(D-M-Y)
*   ?
*/

$app->group('/articles', function() use ($app) {

    $app->get('/', function() use ($app) {
        $response = $app->response();
        $response->header('Access-Control-Allow-Origin', '*');
        $response->write(json_encode("list"));
    });

    $app->group('/categories', function() use ($app) {

        $app->get('/', function() use ($app) {
            //return list of categories
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD);
            $result = $conn->query("SELECT name FROM ez_terms ORDER BY id ASC");
            echo $result;


            $response = $app->response();
            $response->header('Access-Control-Allow-Origin', '*');
            $response->write(json_encode("$result"));
        });

        $app->get('/:category', function($category) use ($app) {
            //return recent articles for specific category
            $response = $app->response();
            $response->header('Access-Control-Allow-Origin', '*');
            $response->write(json_encode("list of articles in category: $category"));
        });

    });

    $app->get('/:id', function($id) use ($app) {
        $response = $app->response();
        $response->header('Access-Control-Allow-Origin', '*');
        $response->write(json_encode("specific article by id $id"));
    });

    //$app->get()
});



$app->run();

?>
