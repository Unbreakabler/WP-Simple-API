<?php
/**
* Builds and updates the comment structure, karma value, and handles user replies TODO(jon): Replies
*/
class CommentAPI {

    /**
    *   Creates a nested json structure of the comments to any depth for display in the app.
    */
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

    /**
    *   Builds the comments into a json object to send to the app, nests children bassed on their parent ID
    */
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

    /**
    *   Adds the comment meta to each comment, this is required to tell if a user has already voted for a comment
    */
    private function getCommentMetaData($table_prefix, $mysqli, $comments) {

        foreach ($comments as $comment) {
            $sql = "SELECT meta_value FROM `".$table_prefix."commentmeta` WHERE meta_key = 'vote' AND comment_id = $comment->comment_ID";
            //var_dump($sql);
            if ($result = $mysqli->query($sql)) {
                $comment->comment_votes = json_decode($result->fetch_array()[0]);
            }
        }

        return $comments;
    }

    /**
    *   Returns a structured list of comments for a post based on the passed in Post ID
    */
    public function getCommentsByPostID() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $post_id = $data['post_id'];
        $mysqli = dbConnect();

        $sql = "SELECT comment_ID,comment_author,comment_author_email,comment_approved,comment_date,comment_content,comment_karma,comment_parent
                FROM `".TABLE_PREFIX."comments` WHERE `comment_post_ID` = $post_id";
        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {

                //Format date into a nicer format to display client side
                $row->comment_date = date('F j, g:i a', strtotime("$row->comment_date"));
                $resultArr[] = $row;
            }
        }

        if (isset($resultArr)) {
            $comments = $this->getCommentMetaData(TABLE_PREFIX, $mysqli, $resultArr);
            $comments = $this->buildCommentStructure($comments);
            return $comments;
        } else {
            if (!isset($res))
                $res = new stdClass();
            $res->error = 204;
            return $res;
        }
        $mysqli->close();
    }

    /**
    *   Returns a structured list of comments for a post based on the passed in comment ID
    *   Used to return the comment object created when a user submits a new comment
    */
    public function getCommentsByID($comment_id) {
        $mysqli = dbConnect();

        $sql = "SELECT comment_ID,comment_author,comment_author_email,comment_approved,comment_date,comment_content,comment_karma,comment_parent
                FROM `".TABLE_PREFIX."comments` WHERE `comment_ID` = $comment_id";
        if ($result = $mysqli->query($sql)) {
            return $result->fetch_object();
        }

        $mysqli->close();
    }

    public function setCommentResponse($table_prefix) {
        $mysqli = dbConnect();

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $comment_date = date("Y-m-d H:i:s");
        $comment_date_gmt = gmdate("Y-m-d H:i:s", time());
        $comment_approved = 0;

        // Check if user is in whitelist
        $sql = "SELECT option_value FROM ".$table_prefix."options WHERE option_name = 'whitelist_keys'";

        if ($result = $mysqli->query($sql)) {
            $whitelist = $result->fetch_array();
            $whitelist_array = explode("\n", $whitelist[0] );
        }

        foreach ($whitelist_array as $user) {
            $user = trim($user);
            if ($data['comment_author_email'] == $user) {
                $comment_approved = 1;
            }
        }

        // Insert comment into the comments table
        $stmt = $mysqli->prepare("INSERT INTO ".$table_prefix."comments (comment_post_ID,comment_author,comment_author_email,comment_date,comment_date_gmt,comment_content,comment_approved,comment_parent,user_id) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('isssssiii', $data['comment_post_ID'], $data['comment_author'], $data['comment_author_email'], $comment_date, $comment_date_gmt, $data['comment_content'], $comment_approved, $data['comment_parent'], $data['user_id']);
        $stmt->execute();
        printf($stmt->error);
        $new_id = $mysqli->insert_id;

        // Increment comment_count on post when new comment is added
        if ($stmt->affected_rows > 0) {
            $sql = "UPDATE `".$table_prefix."posts` SET `comment_count` = `comment_count` + 1 WHERE `ID` = ".$data['comment_post_ID'];
            $mysqli->query($sql);
        }
        $stmt->close();

        $mysqli->close();
        return $new_id;
    }

    public function setNewComment() {
        $mysqli = dbConnect();

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $comment_date = date("Y-m-d H:i:s");
        $comment_date_gmt = gmdate("Y-m-d H:i:s", time());
        $comment_approved = 0;

        // Check if user is in whitelist
        $sql = "SELECT option_value FROM ".TABLE_PREFIX."options WHERE option_name = 'whitelist_keys'";

        if ($result = $mysqli->query($sql)) {
            $whitelist = $result->fetch_array();
            $whitelist_array = explode("\n", $whitelist[0] );
        }

        foreach ($whitelist_array as $user) {
            $user = trim($user);
            if ($data['comment_author_email'] == $user) {
                $comment_approved = 1;
            }
        }

        // Insert comment into the comments table
        $stmt = $mysqli->prepare("INSERT INTO ".TABLE_PREFIX."comments (comment_post_ID,comment_author,comment_author_email,comment_date,comment_date_gmt,comment_content,comment_approved,user_id) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param('isssssii', $data['comment_post_ID'], $data['comment_author'], $data['comment_author_email'], $comment_date, $comment_date_gmt, $data['comment_content'], $comment_approved, $data['user_id']);
        $stmt->execute();
        printf($stmt->error);
        $new_id = $mysqli->insert_id;

        // Increment comment_count on post when new comment is added
        if ($stmt->affected_rows > 0) {
            $sql = "UPDATE `".TABLE_PREFIX."posts` SET `comment_count` = `comment_count` + 1 WHERE `ID` = ".$data['comment_post_ID'];
            $mysqli->query($sql);
        }
        $stmt->close();

        $mysqli->close();
        return $new_id;
    }

    /**
    *
    */
    public function refactoredUpdateCommentKarma() {
        $mysqli = dbConnect();

        $json = file_get_contents('php://input');
        $values = json_decode($json, true);

        $userid = $values['user_id'];
        $comment_id = $values['commentid'];
        $vote = $values['vote'];

        $vote_value = new stdClass();
        $get_vote_value = new stdClass();

        // Check is user is logged input
        if ($userid !== '') {

            $sql = "SELECT comment_karma FROM ".TABLE_PREFIX."comments WHERE comment_ID=".$comment_id;
            $result = $mysqli->query($sql);
            $commentKarma = $result->fetch_array();

            $sql = "SELECT * FROM `".TABLE_PREFIX."commentmeta` WHERE comment_ID=$comment_id AND meta_key='vote'";
            $result = $mysqli->query($sql);
            $commentVotes = $result->fetch_array();
            $get_vote_value = json_decode($commentVotes['meta_value']);

            if (!isset($commentVotes)) {
                //create object
                if ($vote == "voteup") {
                    $vote_value->voteup = 1;
                    $vote_value->votedown = 0;
                    $vote_value->voteup_user = array($userid);
                    $vote_value->votedown_user = array();
                    $newKarma = $this->update_karma($mysqli, TABLE_PREFIX, $vote, $commentKarma, $comment_id, $get_vote_value, $userid);
                } else {
                    $vote_value->voteup = 0;
                    $vote_value->votedown = 1;
                    $vote_value->voteup_user = array();
                    $vote_value->votedown_user = array($userid);
                    $newKarma = $this->update_karma($mysqli, TABLE_PREFIX, $vote, $commentKarma, $comment_id, $get_vote_value, $userid);
                }
                $vote_value = json_encode($vote_value);
                $query = "insert into `".TABLE_PREFIX."commentmeta` (comment_id, meta_key, meta_value) values('" . $comment_id ."', 'vote', '". $vote_value ."')";
                $mysqli->query($query);
                $output = 1;
            } else {
                //get current vote count
                if ($vote == "voteup") {
                    if (in_array($userid, $get_vote_value->votedown_user)) {
                        $newKarma = $this->update_karma($mysqli, TABLE_PREFIX, $vote, $commentKarma, $comment_id, $get_vote_value, $userid);
                        $new_data = $this->change_user_vote($userid, $vote, $get_vote_value->voteup_user, $get_vote_value->votedown_user, $get_vote_value->voteup, $get_vote_value->votedown);
                        unset($get_vote_value->voteup_user);
                        unset($get_vote_value->votedown_user);
                        $get_vote_value->voteup_user = $new_data[0];
                        $get_vote_value->votedown_user = $new_data[1];
                        $get_vote_value->voteup = $new_data[2];
                        $get_vote_value->votedown= $new_data[3];
                        $output = $get_vote_value->voteup;
                    } else if (in_array($userid, $get_vote_value->voteup_user)) {
                        $output = null;
                        // do nothing if user has already sumbitted an upvote
                    } else {
                        $get_vote_value->voteup++;
                        $voteup_user_array = array();
                        $voteup_user_array = $get_vote_value->voteup_user;
                        $voteup_user_array[] = $userid;
                        $get_vote_value->voteup_user = $voteup_user_array;
                        $newKarma = $this->update_karma($mysqli, TABLE_PREFIX, $vote, $commentKarma, $comment_id, $get_vote_value, $userid);
                        $output = $get_vote_value->voteup;
                    }//ends if for pre-vote
                    $vote_value_update = json_encode($get_vote_value);
                    $query = "UPDATE `".TABLE_PREFIX."commentmeta` SET meta_value = '". $vote_value_update ."' WHERE comment_id = '" . $comment_id . "' AND meta_key ='vote'";
                    $mysqli->query($query);
                } else if ($vote == "votedown") {
                    if (in_array($userid, $get_vote_value->voteup_user))  {
                        $newKarma = $this->update_karma($mysqli, TABLE_PREFIX, $vote, $commentKarma, $comment_id, $get_vote_value, $userid);
                        $new_data = $this->change_user_vote($userid, $vote, $get_vote_value->voteup_user, $get_vote_value->votedown_user, $get_vote_value->voteup, $get_vote_value->votedown);
                        unset($get_vote_value->voteup_user);
                        unset($get_vote_value->votedown_user);
                        $get_vote_value->voteup_user = $new_data[0];
                        $get_vote_value->votedown_user = $new_data[1];
                        $get_vote_value->voteup = $new_data[2];
                        $get_vote_value->votedown= $new_data[3];
                        $output = $get_vote_value->votedown;
                    } else if (in_array($userid, $get_vote_value->votedown_user)) {
                        $output = null;
                        // do nothing if user has already sumbitted a downvote
                    } else {
                        $get_vote_value->votedown++;
                        $votedown_user_array = array();
                        $votedown_user_array = $get_vote_value->votedown_user;
                        $votedown_user_array[] = $userid;
                        $get_vote_value->votedown_user = $votedown_user_array;
                        $newKarma = $this->update_karma($mysqli, TABLE_PREFIX, $vote, $commentKarma, $comment_id, $get_vote_value, $userid);
                        $output = $get_vote_value->votedown;
                    }
                    $vote_value_update = json_encode($get_vote_value);
                    $query = "UPDATE `".TABLE_PREFIX."commentmeta` SET meta_value = '". $vote_value_update ."' WHERE comment_id = '" . $comment_id . "' AND meta_key ='vote'";
                    $mysqli->query($query);
                } //ends if
            } //ends for each
        }//ends if userid = ''
        $mysqli->close();
        if ($output) {
            return $newKarma;
        }
        return;
    }

    private function update_karma($mysqli, $table_prefix, $vote_selection, $karma_value, $id, $get_vote_value, $userid){

        if (is_null($karma_value['comment_karma'])) {
            $karma_value['comment_karma'] = 0;
        } // ends if check for null
        if ($vote_selection == 'voteup') {
            if ($get_vote_value) {
                if (in_array($userid, $get_vote_value->votedown_user)) {
                    $karma_value['comment_karma'] += 2;
                }
            } else {
                $karma_value['comment_karma']++;
            }
        }
        if ($vote_selection == 'votedown') {
            if ($get_vote_value) {
                if (in_array($userid, $get_vote_value->voteup_user)) {
                    $karma_value['comment_karma'] -= 2;
                }
            } else {
                $karma_value['comment_karma']--;
            }
        }

        $query = "UPDATE `".$table_prefix."comments` SET comment_karma = '". $karma_value['comment_karma'] ."' where comment_ID ='". $id ."'";

        $mysqli->query($query);

        return intval($karma_value['comment_karma']);

    } // ends function update_karma

    private function change_user_vote($uid, $vote_selection, $u_users, $d_users, $u_vote, $d_vote) {
        $counter = 0;
        $new_u = array();
        $new_d = array();
        if ($vote_selection == 'voteup') {
          // this means previous vote was down. Need to remove old id from down.
          foreach ($d_users as $user) {
              if ($user !== $uid) {
                  // Create new array without the id
                  $new_d[] = $user;
              }
              $counter++;
           }
           array_push ($u_users, $uid);
           $new_u = $u_users;

           $u_vote++;
           $d_vote--;

        } else if ($vote_selection == 'votedown') {
              //previous vote was up
              foreach ($u_users as $user) {
                  if ($user !== $uid) {
                      // Create new array without the id
                      $new_u[] = $user;
                  }
                  $counter++;
              } //ends for each
              array_push ($d_users, $uid);
              $new_d = $d_users;
              $u_vote--;
              $d_vote++;
        }//ends vote selection

        return [$new_u, $new_d, $u_vote, $d_vote];
    }

}
?>
