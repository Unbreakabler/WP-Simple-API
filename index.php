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
require_once('classes/obits-class.php');
require_once('../ezadmin/wp-load.php');

$UserHandler = new UserAPI();
$PostHandler = new PostAPI();
$CommentHandler = new CommentAPI();
$RequestHandler = new requestSender();
$SearchHandler = new SearchAPI();
$AuthHandler = new AuthAPI();
$ObitsHandler = new ObitsAPI();

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

$app->group('/comments', function() use ($app, $PostHandler, $CommentHandler, $RequestHandler, $AuthHandler) {

    $app->post('/', function () use ($app, $CommentHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $CommentHandler->refactoredUpdateCommentKarma();
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->post('/get', function () use ($app, $PostHandler, $CommentHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $CommentHandler->getCommentsByPostID();
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->post('/response', function () use ($app, $CommentHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $new_id = $CommentHandler->setCommentResponse();
        $data = $CommentHandler->getCommentsByID($new_id);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->post('/new', function () use ($app, $CommentHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $new_id = $CommentHandler->setNewComment();
        $data = $CommentHandler->getCommentsByID($new_id);
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

    $app->post('/signup', function () use ($app, $UserHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $UserHandler->userSignUp();
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/search', function() use ($app, $SearchHandler, $RequestHandler, $AuthHandler) {

    $app->post('/', function() use ($app, $SearchHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $SearchHandler->searchPosts();
        $data = $SearchHandler->getSearchRecordCount($data);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->post('/more', function() use ($app, $SearchHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $SearchHandler->searchPosts();
        $data = $SearchHandler->getSearchRecordCount($data);
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/obits', function() use ($app, $ObitsHandler, $PostHandler, $RequestHandler, $AuthHandler) {

    $app->post('/', function() use ($app, $ObitsHandler, $PostHandler, $RequestHandler, $AuthHandler) {
        $AuthHandler->authorizeToken();
        $data = $ObitsHandler->getPostByType();
        $data = $PostHandler->getPostMetaData($data);
        $RequestHandler->sendJSONResponse($app, $data);
    });

});

$app->run();
?>
