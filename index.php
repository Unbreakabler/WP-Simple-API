<?php

//Framework for a simple RESTful API implementation
require 'Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

//Grabbing the DB credentials to acces the necessary SQL tables
require 'config/config.php';
require 'classes/post-class.php';
require 'classes/requestsender-class.php';
require 'classes/comment-class.php';
require 'classes/user-class.php';
require 'classes/search-class.php';

$UserHandler = new UserAPI();
$PostHandler = new PostAPI();
$CommentHandler = new CommentAPI();
$RequestHandler = new requestSender();
$SearchHandler = new SearchAPI();

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

    $app->get('/:category_id', function($category_id) use ($app, $PostHandler, $RequestHandler) {
        $postArray = $PostHandler->getRecentPostsByCategory(TABLE_PREFIX, $category_id);
        $postListArray = $PostHandler->getPostListByID(TABLE_PREFIX, $postArray);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $postListArray, $category_id);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->get ('/:category_id/:count', function($category_id,$count) use ($app, $PostHandler, $RequestHandler) {
        $postArray = $PostHandler->getRecentPostsByCategory(TABLE_PREFIX, $category_id, $count);
        $postListArray = $PostHandler->getPostListByID(TABLE_PREFIX, $postArray);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $postListArray, $category_id);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->get ('/:category_id/:count/:index', function($category_id, $count, $index) use ($app, $PostHandler, $RequestHandler) {
        $postArray = $PostHandler->getRecentPostsByCategory(TABLE_PREFIX, $category_id, $count, $index);
        $postListArray = $PostHandler->getPostListByID(TABLE_PREFIX, $postArray);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $postListArray, $category_id);
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/post', function() use ($app, $PostHandler, $RequestHandler) {

    //TODO: Implement grabbing the post purely by ID, 'category_id' (Category) is only required for next/prev

    $app->get('/:category_id/:post_id', function($category_id, $post_id) use ($app, $PostHandler, $RequestHandler) {
        $data = $PostHandler->getPostByID(TABLE_PREFIX, $post_id);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $data);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->get('/:category_id/:post_id/prev', function($category_id, $post_id) use ($app, $PostHandler, $RequestHandler) {
        $data = $PostHandler->getPreviousPostByID(TABLE_PREFIX, $post_id, $category_id);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $data);
        $RequestHandler->sendJSONResponse($app, $data);
    });

    $app->get('/:category_id/:post_id/next', function($category_id, $post_id) use ($app, $PostHandler, $RequestHandler) {
        $data = $PostHandler->getNextPostByID(TABLE_PREFIX, $post_id, $category_id);
        $data = $PostHandler->getPostMetaData(TABLE_PREFIX, $data);
        $RequestHandler->sendJSONResponse($app, $data);
    });

});

$app->group('/gallery', function () use ($app, $PostHandler, $RequestHandler) {

    $app->post('/', function () use ($app, $PostHandler, $RequestHandler) {
        $data = $PostHandler->getGalleryMeta(TABLE_PREFIX);
        $data = $PostHandler->buildGalleryLinks($data);
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

$app->group('/user', function () use ($app, $UserHandler, $RequestHandler) {
    $app->get('/', function () use ($app, $UserHandler, $RequestHandler) {
        $data = $UserHandler->getUserByToken(TABLE_PREFIX);
        $RequestHandler->sendJSONResponse($app, $data);
    });
});

$app->group('/search', function() use ($app, $SearchHandler, $RequestHandler) {

  $app->get('/:search_key/:count', function($search_key, $count) use ($app, $SearchHandler, $RequestHandler) {
      $data = $SearchHandler->searchPosts(TABLE_PREFIX, $search_key, $count);
      $data = $SearchHandler->getSearchRecordCount(TABLE_PREFIX, $search_key, $data);
      $RequestHandler->sendJSONResponse($app, $data);
  });
  $app->get('/:search_key/:count/:index', function($search_key, $count, $index) use ($app, $SearchHandler, $RequestHandler) {
      $data = $SearchHandler->searchPosts(TABLE_PREFIX, $search_key, $count, $index);
      $RequestHandler->sendJSONResponse($app, $data);
  });

});

$app->run();

?>
