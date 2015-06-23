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

    private function getNestedChildren($comments, $parent) {
        $output = [];

        for ($i = 0; $i < count($comments); $i++) {
            if($comments[$i]->comment_parent == $parent) {
                //var_dump($comments[$i], $parent);
                $children = $this->getNestedChildren($comments, $comments[$i]->comment_ID);

                if (sizeof($children) > 0) {
                    $comments[$i]->child = $children;
                }
                array_push($output, $comments[$i]);
            }
        }
        //var_dump($output);
        return $output;
    }


    private function buildCommentStructure($comments) {
        $newComments = [];
        foreach ($comments as $comment) {
            if ($comment->comment_parent == "0") {
                $res = $this->getNestedChildren($comments, $comment->comment_ID);
                if ($res) {
                    $comment->child = $res;
                }
                array_push($newComments, $comment);
            }
        }
        return $newComments;
    }
        // $count = count($comments);
        // for ($i = 0; $i < $count; $i++) {
        //     if ($comments[$i]->comment_parent == '0') {
        //         $parentComments[] = ($comments[$i]);
        //         unset($comments[$i]);
        //
        //     }
        // }
        // $comments = array_values($comments);
        //
        // $count = count($comments);
        //
        // $finalComments = $this->commentNesting($comments, $parentComments);
        //
        // foreach ($comments as $comment) {
        //     foreach ($parentComments as $parent) {
        //         if (isset($parent->child)) {
        //             foreach ($parent->child as $child) {
        //                 if ($child->comment_ID == $comment->comment_parent) {
        //                     $child->child[] = $comment;
        //                 }
        //             }
        //         }
        //         if ($parent->comment_ID == $comment->comment_parent) {
        //             $parent->child[] = $comment;
        //         }
        //     }
        // }
        //
        // return $parentComments;

    /*
    private function commentNesting($comments, $parentComments) {

        foreach ($comments as $comment) {
            foreach ($parentComments as $parent) {
                ;
            }

        }

        return $comments;
    }
    */

    public function getCommentsByID($table_prefix, $post_id) {
        $mysqli = dbConnect();

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


    }

}
?>
