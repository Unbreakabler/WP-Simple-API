<?php

//Framework for a simple RESTful API implementation
require 'Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

//Grabbing the DB credentials to acces the necessary SQL tables
require 'config/config.php';
var_dump($categories);

//Creates a new mysqli connection to database
function dbConnect($host = DB_HOST, $user = DB_USER, $password = DB_PASSWORD, $dbname = DB_NAME) {
    $mysqli = new mysqli($host, $user, $password, $dbname);
    if ($mysqli->connect_error) {
        die("Connection failled:" . $mysqli->connect_error);
    } else {
        return $mysqli;
    }
}

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




// OLD CODE BELOW HERE
/** CATEGORY FUNCTIONS **/
/*
// Returns an unfiltered list of all of the categories present on the wordpress site
function getFullCategoryList($mysqli, $table_prefix) {
    if ($result = $mysqli->query("SELECT term_id,slug,name FROM " . $table_prefix . "terms")) {
        while($row = $result->fetch_array(MYSQL_ASSOC)) {
            $resultArray[] = ($row);
        }
    } else {
        return 'categories failed';
    }
    return $resultArray;
}

//Returns a list of term_id for categories that have more posts then the minimum required in the config file
function getCommonCategories($mysqli, $table_prefix, $postCount) {
    $count = 0;
    if ($result = $mysqli->query("SELECT term_id FROM " . $table_prefix . "term_taxonomy WHERE count > " . $postCount)) {
        while($row = $result->fetch_array(MYSQL_ASSOC)) {
            $resultArray[] = ($row['term_id']);
        }
    }
    return $resultArray;
}

//Trims the full categoryList down to only those categories with postcount > minimum required
function trimCategories($catList, $termIds) {
    foreach ($termIds as $id) {
        foreach ($catList as $item) {
            if ($item['term_id'] == $id) {
                $resultArray[] = $item;
            }
        }
    }
    return $resultArray;
}

$app->group('/articles', function() use ($app) {

    $app->get('/', function() use ($app) {
        $response = $app->response();
        $response->header('Access-Control-Allow-Origin', '*');
        $response->write(json_encode("list"));
    });

    $app->group('/categories', function() use ($app) {

        $app->get('/', function() use ($app) {  //return list of categories

            $mysqli = dbConnect();
            $fullCategoryList = getFullCategoryList($mysqli, TABLE_PREFIX);
            $termidsToKeep = getCommonCategories($mysqli, TABLE_PREFIX, CATEGORY_POST_COUNT);
            $categoryList = trimCategories($fullCategoryList, $termidsToKeep);

            // CREATE RESPONSE
            $response = $app->response();
            // Allow access to everyone for initial testing purposes
            // TODO: Implemention authentication protocol so the API can only be accessed by the app
            $response->header('Access-Control-Allow-Origin', '*');
            $response->write(json_encode($categoryList));
        });

        $app->get('/:category', function($category) use ($app) {
            //TODO: Implement a CategoryName => term_id relationship server side.
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
*/


$app->run();

?>
