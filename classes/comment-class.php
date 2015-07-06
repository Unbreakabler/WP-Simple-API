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

    public function getCommentsByID($table_prefix, $post_id) {
        $mysqli = dbConnect();

        $sql = "SELECT comment_ID,comment_author,comment_date,comment_content,comment_karma,comment_parent
                FROM `".$table_prefix."comments` WHERE `comment_post_ID` = $post_id AND `comment_approved` = 1";
        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {

                //Format date into a nicer format to display client side
                $row->comment_date = date('F j, g:i a', strtotime("$row->comment_date"));
                $resultArr[] = $row;
            }
        }





        if (isset($resultArr)) {
            //var_dump($resultArr);
            $comments = $this->getCommentMetaData($table_prefix, $mysqli, $resultArr);
            $comments = $this->buildCommentStructure($comments);
            //var_dump($comments);
            return $comments;
        } else {
            if (!isset($res))
                $res = new stdClass();
            $res->error = 204;
            return $res;
        }
        $mysqli->close();
    }

    public function refactoredUpdateCommentKarma($table_prefix) {
        $mysqli = dbConnect();

        $json = file_get_contents('php://input');
        $values = json_decode($json, true);
        $post_id = $values['post_id'];
        $userid = $values['user_id'];
        $comment_id = $values['commentid'];
        $vote = $values['vote'];
        $prev_vote = $values['prev_vote'];
        $vote_value = new stdClass();

        // Check is user is logged input
        if ($userid !== '') {
            $sql = "SELECT `comment_karma` FROM `".$table_prefix."comments` WHERE comment_ID='".$comment_id"'";
            $result = $mysqli->query($sql);
            $commentKarma = $result->fetch_array();

            $sql = "SELECT * FROM `".$table_prefix."commentmeta` WHERE `comment_ID`='".$comment_id"' AND `meta_key`='vote'";
            $result = $mysqli->query($sql)
            $commentVotes = $result->fetch_array();

            if (!isset($commentVotes)) {
              //create object
              if ($vote == "voteup") {
                  $vote_value->voteup = 1;
                  $vote_value->votedown = 0;
                  $vote_value->voteup_user = array($userid);
                   = '';
                  update_karma($mysqli, $vote, $commentKarma, $comment_id);
              } else  ($vote == "votedown") {
                  $vote_value->voteup = 0;
                  $vote_value->votedown = 1;
                  $voteup_userid = '';
                  $vote_value->votedown_user = array($votedown_userid);
                  $this->update_karma($mysqli, $vote, $commentKarma, $comment_id);
              }

              $vote_value = json_encode($vote_value);

              $query = "insert into `ez_commentmeta` (comment_id, meta_key, meta_value) values('" . $comment_id ."', 'vote', '". $vote_value ."')";
              $mysqli->query($query);
              $output = $vote_value->voteup;
              if ($output == '') {$output=$vote_value->votedown;}
              echo $output;

            } else {
              //get current vote count
              foreach ($result_array as $result) {
                  $get_vote_value = new stdClass();
                  $get_vote_value = json_decode($result['meta_value']);

                  if ($vote == "voteup") {

                    if ($prev_vote == 'voted') {
                        $new_data = $this->change_user_vote($userid, $vote, $get_vote_value->voteup_user, $get_vote_value->votedown_user, $get_vote_value->voteup, $get_vote_value->votedown);
                        //var_dump($new_data[0]);
                        unset($get_vote_value->voteup_user);
                        unset($get_vote_value->votedown_user);
                        $get_vote_value->voteup_user = $new_data[0];
                        $get_vote_value->votedown_user = $new_data[1];
                        $get_vote_value->voteup = $new_data[2];
                        $get_vote_value->votedown= $new_data[3];

                    } else {
                        $get_vote_value->voteup++;
                        $voteup_user_array = array();
                        $voteup_user_array = $get_vote_value->voteup_user;
                        $voteup_user_array[] = $userid;
                        $get_vote_value->voteup_user = $voteup_user_array;
                    }//ends if for pre-vote

                    $vote_value_update = json_encode($get_vote_value);
                    $query = "update ez_commentmeta set meta_value = '". $vote_value_update ."' where comment_id = '" . $comment_id . "' and meta_key ='vote'";
                    $mysqli->query($query);
                    $output = $get_vote_value->voteup;
                    $this->update_karma($mysqli, $vote, $commentKarma, $comment_id);
                    echo $output;

                  } else if ($vote == "votedown") {

                    if ($prev_vote == 'voted') {
                        $new_data = $this->change_user_vote($userid, $vote, $get_vote_value->voteup_user, $get_vote_value->votedown_user, $get_vote_value->voteup, $get_vote_value->votedown);
                        unset($get_vote_value->voteup_user);
                        unset($get_vote_value->votedown_user);
                        $get_vote_value->voteup_user = $new_data[0];
                        $get_vote_value->votedown_user = $new_data[1];
                        $get_vote_value->voteup = $new_data[2];
                        $get_vote_value->votedown= $new_data[3];

                    } else {
                        $get_vote_value->votedown++;
                        $votedown_user_array = array();
                        $votedown_user_array = $get_vote_value->votedown_user;
                        $votedown_user_array[] = $userid;
                        $get_vote_value->votedown_user = $votedown_user_array;
                    }

                    $vote_value_update = json_encode($get_vote_value);
                    $query = "update ez_commentmeta set meta_value = '". $vote_value_update ."' where comment_id = '" . $comment_id . "' and meta_key ='vote'";
                    $mysqli->query($query);
                    $output = $get_vote_value->votedown;
                    $this->update_karma($mysqli, $vote, $commentKarma, $comment_id);
                    echo $output;


                  } //ends if
              } //ends for each
            }//ends else
          }//ends if userid = ''
          $mysqli->close();
    }

    public function updateCommentKarma($table_prefix) {
        //header("Access-Control-Allow-Origin: *");

        $mysqli = dbConnect();
        /************************************************
        	Search Functionality
        ************************************************/

        // Define Output HTML Formating
        $json = file_get_contents('php://input');
        $values = json_decode($json, true);

        //var_dump($values);

        $post_id = $values['post_id'];
        $userid = $values['user_id'];
        $comment_id = $values['commentid'];
        $vote = $values['vote'];
        $prev_vote = $values['prev_vote'];
        // Check Length More Than One Character
        if ($userid !== '') {
        // Build Query

          $karma_query = "SELECT comment_karma from ez_comments where comment_ID='". $comment_id ."'";
          $karma_result = $mysqli->query($karma_query);

          while($karma_results = $karma_result->fetch_array()) {
            $karma_result_array[] = $karma_results;
          }

          $query = "SELECT * FROM ez_commentmeta WHERE comment_id ='" .$comment_id ."' AND meta_key='vote'";

          $result = $mysqli->query($query);

          while($results = $result->fetch_array()) {
        	$result_array[] = $results;
          }

          if (!isset($result_array)) {
            //create object
            if ($vote == "voteup") {
              $voteup = 1;
              $votedown = 0;
              $voteup_userid = $userid;
              $votedown_userid = '';
              update_karma($mysqli, $vote, $karma_result_array, $comment_id);

            } else if ($vote == "votedown") {
              $voteup = 0;
              $votedown = 1;
              $voteup_userid = '';
              $votedown_userid = $userid;
              $this->update_karma($mysqli, $vote, $karma_result_array, $comment_id);
            }
            $vote_value = new stdClass();
            $vote_value->voteup = $voteup;
            $vote_value->voteup_user = array($voteup_userid);
            $vote_value->votedown = $votedown;
            $vote_value->votedown_user = array($votedown_userid);

            $vote_value_insert = json_encode($vote_value);

            $query = "insert into `ez_commentmeta` (comment_id, meta_key, meta_value) values('" . $comment_id ."', 'vote', '". $vote_value_insert ."')";
            $mysqli->query($query);
            $output = $vote_value->voteup;
            if ($output == '') {$output=$vote_value->votedown;}
            echo $output;

          } else {
            //get current vote count
            foreach ($result_array as $result) {
                $get_vote_value = new stdClass();
                $get_vote_value = json_decode($result['meta_value']);

                if ($vote == "voteup") {

                  if ($prev_vote == 'voted') {
                      $new_data = $this->change_user_vote($userid, $vote, $get_vote_value->voteup_user, $get_vote_value->votedown_user, $get_vote_value->voteup, $get_vote_value->votedown);
                      //var_dump($new_data[0]);
                      unset($get_vote_value->voteup_user);
                      unset($get_vote_value->votedown_user);
                      $get_vote_value->voteup_user = $new_data[0];
                      $get_vote_value->votedown_user = $new_data[1];
                      $get_vote_value->voteup = $new_data[2];
                      $get_vote_value->votedown= $new_data[3];

                  } else {
                      $get_vote_value->voteup++;
                      $voteup_user_array = array();
                      $voteup_user_array = $get_vote_value->voteup_user;
                      $voteup_user_array[] = $userid;
                      $get_vote_value->voteup_user = $voteup_user_array;
                  }//ends if for pre-vote

                  $vote_value_update = json_encode($get_vote_value);
                  $query = "update ez_commentmeta set meta_value = '". $vote_value_update ."' where comment_id = '" . $comment_id . "' and meta_key ='vote'";
                  $mysqli->query($query);
                  $output = $get_vote_value->voteup;
                  $this->update_karma($mysqli, $vote, $karma_result_array, $comment_id);
                  echo $output;

                } else if ($vote == "votedown") {

                  if ($prev_vote == 'voted') {
                      $new_data = $this->change_user_vote($userid, $vote, $get_vote_value->voteup_user, $get_vote_value->votedown_user, $get_vote_value->voteup, $get_vote_value->votedown);
                      unset($get_vote_value->voteup_user);
                      unset($get_vote_value->votedown_user);
                      $get_vote_value->voteup_user = $new_data[0];
                      $get_vote_value->votedown_user = $new_data[1];
                      $get_vote_value->voteup = $new_data[2];
                      $get_vote_value->votedown= $new_data[3];

                  } else {
                      $get_vote_value->votedown++;
                      $votedown_user_array = array();
                      $votedown_user_array = $get_vote_value->votedown_user;
                      $votedown_user_array[] = $userid;
                      $get_vote_value->votedown_user = $votedown_user_array;
                  }

                  $vote_value_update = json_encode($get_vote_value);
                  $query = "update ez_commentmeta set meta_value = '". $vote_value_update ."' where comment_id = '" . $comment_id . "' and meta_key ='vote'";
                  $mysqli->query($query);
                  $output = $get_vote_value->votedown;
                  $this->update_karma($mysqli, $vote, $karma_result_array, $comment_id);
                  echo $output;


                } //ends if
            } //ends for each
          }//ends else
        }//ends if userid = ''
        $mysqli->close();
    }

    private function update_karma($mysqli, $vote_selection, $karma_value, $id){

        foreach ($karma_value as $result) {
            if (is_null($result['comment_karma'])) {
                $result['comment_karma'] = 0;
            } // ends if check for null
            if ($vote_selection == 'voteup') {
                $result['comment_karma']++;
            } else if ($vote_selection == 'votedown') {
                $result['comment_karma']--;
            }// ends if $vote_selection
        } // ends for each
        $query = "UPDATE ez_comments SET comment_karma = '". $result['comment_karma'] ."' where comment_ID ='". $id ."'";

        $mysqli->query($query);

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
