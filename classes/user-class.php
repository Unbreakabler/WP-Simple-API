<?php

/*  News Article API
*
*   - Get a default list of recent articles from specific category -> /category/:term_id
*   - Get list of articles from specific category -> /category/:term_id(categoryID)/:count(numOfPostsToReturn)/:index(lastPostID)
*   - Get specific article (title/body/views/datePosted/author/images/gallery) -> /article/:id(postID)
*   ? Get list of article categories from /articles/categories (May be usful in the future if we do subcategories)
*   ? Get specific article by name from /articles/:name
*   ? Get list of articles on a certain date from /articles/:date(D-M-Y)
*/

//TODO: Define the names for metadata values in the config file ex (tie_views is most likely not the name for views in every wordpress db)
//TODO: Define update funciton to increment the view count

class UserAPI {

    private function getUserInfo($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,
            $url . $_GET['access_token']
        );
        $content = curl_exec($ch);
        echo $content;
    }

    public function getUserByToken($table_prefix) {

        // switch to query different restful APIs based on login_service the users access token came from

        switch ($_GET['login_service']) {
            case 'facebook':
                $user = $this->getUserInfo('https://graph.facebook.com/v2.2/me/?access_token=');
                # code...
                break;

            case 'twitter':
                # code...
                break;

            case 'google':
                # code...
                break;

            default:
                # code...
                break;
        }

        $mysqli = dbConnect();

        return $user;
        /*
        $sql = "SELECT comment_ID,comment_author,comment_date,comment_content,comment_karma,comment_parent
                FROM `".$table_prefix."comments` WHERE `comment_post_ID` = $post_id AND `comment_approved` = 1";

        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {

                //Format date into a nicer format to display client side
                $row->comment_date = date('F j, Y, g:i a', strtotime("$row->comment_date"));
                $finalResult[] = $row;
            }
        }
        $mysqli->close();

        if (isset($finalResult)) {
            //var_dump($finalResult);
            $comments = $this->buildCommentStructure($finalResult);
            //var_dump($comments);
            return $comments;
        } else {
            return 204;
        }
        */

    }

}
?>
