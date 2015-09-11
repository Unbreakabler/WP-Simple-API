<?php
//Framework for a simple RESTful API implementation
require_once('Slim/Slim/Slim.php');
\Slim\Slim::registerAutoloader();

//Grabbing the DB credentials to acces the necessary SQL tables
require_once('config/config.php');
require_once('classes/post-class.php');
require_once('classes/requestsender-class.php');
require_once('classes/comment-class.php');
require_once('classes/user-class.php');
require_once('classes/search-class.php');
require_once('classes/auth-class.php');
require_once('../ezadmin/wp-load.php');

$UserHandler = new UserAPI();
$PostHandler = new PostAPI();
$CommentHandler = new CommentAPI();
$RequestHandler = new requestSender();
$SearchHandler = new SearchAPI();
$AuthHandler = new AuthAPI();

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

$app->group('/category', function () use ($app, $PostHandler, $RequestHandler, $AuthHandler) {

    $app->post('/', function() use ($app, $PostHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $postArray = $PostHandler->getRecentPostsByCategory();
        //$catId = array_pop($postArray);
        $postListArray = $PostHandler->getPostListByID($postArray);
        $data = $PostHandler->getPostMetaData($postListArray);
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/post', function() use ($app, $PostHandler, $RequestHandler, $AuthHandler) {

    $app->post('/', function() use ($app, $PostHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $PostHandler->getPostByType();
        //$data = $PostHandler->getPostByID();
        $data = $PostHandler->getPostMetaData($data);
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/gallery', function () use ($app, $PostHandler, $RequestHandler, $AuthHandler) {

    $app->post('/', function () use ($app, $PostHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $PostHandler->getGalleryMeta();
        $data = $PostHandler->buildGalleryLinks($data);
        $RequestHandler->sendJSONResponse($app, $data);
    });

});

$app->group('/trending', function () use ($app, $PostHandler, $RequestHandler, $AuthHandler) {

    $app->post('/', function () use ($app, $PostHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $PostHandler->getTrendingStories();
        $data = $PostHandler->getPostMetaData($data);
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/comments', function() use ($app, $PostHandler, $CommentHandler, $RequestHandler) {

    //Get all comments for current post_id
    $app->get('/:post_id', function ($post_id) use ($app, $PostHandler, $CommentHandler, $RequestHandler) {
        $data = $CommentHandler->getCommentsByPostID(TABLE_PREFIX, $post_id);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->post('/', function () use ($app, $CommentHandler, $RequestHandler) {
        $data = $CommentHandler->refactoredUpdateCommentKarma(TABLE_PREFIX);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->post('/response', function () use ($app, $CommentHandler, $RequestHandler) {
        $new_id = $CommentHandler->setCommentResponse(TABLE_PREFIX);
        $data = $CommentHandler->getCommentsByID(TABLE_PREFIX, $new_id);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->post('/new', function () use ($app, $CommentHandler, $RequestHandler) {
        $new_id = $CommentHandler->setNewComment(TABLE_PREFIX);
        $data = $CommentHandler->getCommentsByID(TABLE_PREFIX, $new_id);
        $RequestHandler->sendJSONResponse($app, $data);
    });

});


// Basic authentication in place
$app->group('/user', function () use ($app, $UserHandler, $RequestHandler, $AuthHandler) {
    $app->post('/', function () use ($app, $UserHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $UserHandler->getUserByToken();
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->post('/signup', function () use ($app, $UserHandler, $RequestHandler) {
        $AuthHandler->authorizeToken();
        $data = $UserHandler->userSignUp();
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/search', function() use ($app, $SearchHandler, $RequestHandler) {

    $app->get('/:search_key/:count', function($search_key, $count) use ($app, $SearchHandler, $RequestHandler) {
        $data = $SearchHandler->searchPosts($search_key, $count);
        $data = $SearchHandler->getSearchRecordCount($search_key, $data);
        $RequestHandler->sendJSONResponse($app, $data);
    });
    $app->get('/:search_key/:count/:index', function($search_key, $count, $index) use ($app, $SearchHandler, $RequestHandler) {
        $data = $SearchHandler->searchPosts($search_key, $count, $index);
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->run();
?>
