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

class CommentAPI {


    //TODO: Reimplement this function to be comment depth agnostic
    // currently only deals with comments to a depth of 3
    private function buildCommentStructure($comments) {
        $count = count($comments);
        for ($i = 0; $i < $count; $i++) {
            if ($comments[$i]->comment_parent == '0') {
                $newComments[] = ($comments[$i]);
                unset($comments[$i]);

            }
        }
        $comments = array_values($comments);

        $count = count($comments);

        foreach ($comments as $comment) {
            foreach ($newComments as $parent) {
                if (isset($parent->child)) {
                    foreach ($parent->child as $child) {
                        if ($child->comment_ID == $comment->comment_parent) {
                            $child->child[] = $comment;
                        }
                    }
                }
                if ($parent->comment_ID == $comment->comment_parent) {
                    $parent->child[] = $comment;
                }
            }
        }

        var_dump($newComments);
        return $newComments;
    }

    public function getCommentsByID($table_prefix, $post_id) {
        $mysqli = dbConnect();

        $sql = "SELECT comment_ID,comment_author,comment_date,comment_content,comment_karma,comment_parent
                FROM `".$table_prefix."comments` WHERE `comment_post_ID` = $post_id AND `comment_approved` = 1";
        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {
                $finalResult[] = $row;
            }
        }
        $mysqli->close();

        $comments = $this->buildCommentStructure($finalResult);

        //return $comments;
    }

}
?>
