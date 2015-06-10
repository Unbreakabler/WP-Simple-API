<?php

//Framework for a simple RESTful API implementation
require 'Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

//Grabbing the DB credentials to acces the necessary SQL tables
require 'config/config.php';
//var_dump($categories);

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
*   - Get a default list of recent articles from specific category -> /category/:term_id
*   - Get list of articles from specific category -> /category/:term_id(categoryID)/:count(numOfPostsToReturn)/:index(lastPostID)
*   - Get specific article (title/body/views/datePosted/author/images/gallery) -> /article/:id(postID)
*   ? Get list of article categories from /articles/categories (May be usful in the future if we do subcategories)
*   ? Get specific article by name from /articles/:name
*   ? Get list of articles on a certain date from /articles/:date(D-M-Y)
*/

function getRecentPostsByCategory($table_prefix, $term_id = 2, $count = 5, $index = 0) {
    $mysqli = dbConnect();

    //TODO: Remove else, set indexDate default to current date - format: '2015-01-23 14:33:02'
    //TODO: Close all mysqli objects

    $indexDate = new DateTime();
    $indexDate = $indexDate->format( 'Y-m-d H:i:s');

    if ($index !== 0) {
        $indexDate = findIndexDate($index, $mysqli);
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS ez_posts.ID
            FROM ez_posts INNER JOIN ez_term_relationships ON
            (ez_posts.ID = ez_term_relationships.object_id) WHERE 1=1
            AND ( ez_term_relationships.term_taxonomy_id IN ($term_id) )
            AND ez_posts.post_type = 'post'
            AND (ez_posts.post_status = 'publish'
            OR ez_posts.post_status = 'expired'
            OR ez_posts.post_status = 'private')
            AND ez_posts.post_date < '$indexDate'
            GROUP BY ez_posts.ID ORDER BY ez_posts.post_date
            DESC LIMIT 0, $count";


    if ($result = $mysqli->query($sql)) {
        while($row = $result->fetch_object()) {
            $resultArray[] = $row;
        }
    } else {
        return 'Select Statement Failed';
    }

    return $resultArray;
}

function findIndexDate($index, $mysqli) {

    $sql = "SELECT ez_posts.post_date FROM ez_posts WHERE ez_posts.ID = $index";

    if ($result = $mysqli->query($sql)) {
        while($row = $result->fetch_object()) {
            $resultField = $row->post_date;
        }
    }
    return($resultField);
}

function sendJSONResponse($app, $responseBody) {
    // CREATE RESPONSE
    $response = $app->response();
    // Allow access to everyone for initial testing purposes
    // TODO: Implemention authentication protocol so the API can only be accessed by the app
    $response->header('Access-Control-Allow-Origin', '*');
    $response->write(json_encode($responseBody));
}

$app->group('/category', function () use ($app) {

    $app->get('/:term_id', function($term_id) use ($app) {
        $resultArray = getRecentPostsByCategory(TABLE_PREFIX, $term_id);
        sendJSONResponse($app, $resultArray);
    });

    $app->get ('/:term_id/:count', function($term_id,$count) use ($app) {
        $resultArray = getRecentPostsByCategory(TABLE_PREFIX, $term_id, $count);
        sendJSONResponse($app, $resultArray);
    });

    $app->get ('/:term_id/:count/:index', function($term_id, $count, $index) use ($app) {
        $resultArray = getRecentPostsByCategory(TABLE_PREFIX, $term_id, $count, $index);
        sendJSONResponse($app, $resultArray);
    });
});

$app->get('/category/:term_id', function ($term_id) use ($app) {




});


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
