<?php

class CommentAPI {

    private function getNestedChildren($comments, $parent) {
        $output = [];

        for ($i = 0; $i < count($comments); $i++) {
            if($comments[$i]->comment_parent == $parent) {
                $children = $this->getNestedChildren($comments, $comments[$i]->comment_ID);

                if (sizeof($children) > 0) {
                    $comments[$i]->child = $children;
                }
                array_push($output, $comments[$i]);
            }
        }
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

    public function getCommentsByID($table_prefix, $post_id) {
        $mysqli = dbConnect();

        $sql = "SELECT comment_ID,comment_author,comment_date,comment_content,comment_karma,comment_parent
                FROM `".$table_prefix."comments` WHERE `comment_post_ID` = $post_id AND `comment_approved` = 1";
        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {

                //Format date into a nicer format to display client side
                $row->comment_date = date('F j, g:i a', strtotime("$row->comment_date"));
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
            if (!isset($res))
                $res = new stdClass();
            $res->error = 204;
            return $res;
        }


    }

}
?>
