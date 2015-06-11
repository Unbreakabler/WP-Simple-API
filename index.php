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
        $mysqli->set_charset("utf8");
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
//TODO: Close all mysqli objects
//TODO: Create a class for all of the New Article API functions, refactor them to their own file.

function jsonErrorTesting() {
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - No errors';
        break;
        case JSON_ERROR_DEPTH:
            echo ' - Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
            echo ' - Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
            echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        default:
            echo ' - Unknown error';
        break;
    }
}

function setIndexDate($index, $mysqli) {
    $indexDate = new DateTime();
    $indexDate = $indexDate->format( 'Y-m-d H:i:s');

    if ($index !== 0) {
        $indexDate = findIndexDate($index, $mysqli);
    }
    return $indexDate;
}

function getRecentPostsByCategory($table_prefix, $term_id = 2, $count = 5, $index = 0) {
    $mysqli = dbConnect();

    $indexDate = setIndexDate($index, $mysqli);

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

    $mysqli->close();
    return $resultArray;
}

function findIndexDate($index, $mysqli) {

    $sql = "SELECT ez_posts.post_date FROM ez_posts WHERE ez_posts.ID = $index";
    $resultField = 'ID NOT FOUND';

    if ($result = $mysqli->query($sql)) {
        while($row = $result->fetch_object()) {
            $resultField = $row->post_date;
        }
    }
    return $resultField ;
}

function getPostListByID($table_prefix, $ids) {
    $mysqli = dbConnect();
    $idString = '';

    foreach ($ids as $id) {
        $idString .= $id->ID . ',';
    }

    $idString = rtrim($idString, ",");
    $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_status FROM ".$table_prefix."posts WHERE `ID` IN ($idString)";

    if ($result = $mysqli->query($sql)) {
        while ($row = $result->fetch_object()) {
            $resultField[] = $row;
        }
    }
    $mysqli->close();
    return $resultField;
}

function getPostMetaData($table_prefix, $posts) {
    $mysqli = dbConnect();


    foreach ($posts as $post) {
        //Appends the header image to each post object
        $sql = "SELECT * FROM ".$table_prefix."postmeta WHERE post_id = $post->ID AND meta_key IN ('tie_views','_thumbnail_id')";

        //var_dump($sql);
        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {
                if ($row->meta_key == '_thumbnail_id') {
                    //$post->thumbnailID = $row->meta_value;
                    $sql = "SELECT * FROM `ez_posts` WHERE `ID` = $row->meta_value AND `post_type` LIKE 'attachment'";
                    if ($newresult = $mysqli->query($sql)) {
                        while ($row = $newresult->fetch_object()) {
                            $post->thumbnailURI = $row->guid;
                        }
                    }
                } else {
                    //var_dump($row->meta_value);
                    $post->views = $row->meta_value;
                }
            }
        }
        //Appends the real name of the author to each post object
        $sql = "SELECT `display_name`FROM `ez_users` WHERE `ID` = $post->post_author";
        if ($result = $mysqli->query($sql)) {
            $obj = $result->fetch_object();
            $post->author_name = $obj->display_name;
        }

        if (!isset($post->thumbnailURI)) {
            $post->thumbnailURI = DEFAULT_POST_IMAGE;
        }
    }

    //var_dump($posts);
    $mysqli->close();
    return $posts;
    //var_dump($thumbnailID);
}

function sendJSONResponse($app, $responseBody) {
    $response = $app->response();
    // Allow access to everyone for initial testing purposes
    // TODO: Implemention authentication protocol so the API can only be accessed by the app
    $response->header('Access-Control-Allow-Origin', '*');

    //jsonErrorTesting();

    $response->write(json_encode($responseBody, JSON_HEX_QUOT | JSON_HEX_TAG));
}

$app->group('/category', function () use ($app) {

    $app->get('/:term_id', function($term_id) use ($app) {
        $postArray = getRecentPostsByCategory(TABLE_PREFIX, $term_id);
        $postListArray = getPostListByID(TABLE_PREFIX, $postArray);
        $resultArray = getPostMetaData(TABLE_PREFIX, $postListArray);
        sendJSONResponse($app, $resultArray);
    });

    $app->get ('/:term_id/:count', function($term_id,$count) use ($app) {
        $postArray = getRecentPostsByCategory(TABLE_PREFIX, $term_id, $count);
        $postListArray = getPostListByID(TABLE_PREFIX, $postArray);
        $resultArray = getPostMetaData(TABLE_PREFIX, $postListArray);
        sendJSONResponse($app, $resultArray);
    });

    $app->get ('/:term_id/:count/:index', function($term_id, $count, $index) use ($app) {
        $postArray = getRecentPostsByCategory(TABLE_PREFIX, $term_id, $count, $index);
        $postListArray = getPostListByID(TABLE_PREFIX, $postArray);
        $resultArray = getPostMetaData(TABLE_PREFIX, $postListArray);
        sendJSONResponse($app, $resultArray);
    });
});

$app->run();

?>
