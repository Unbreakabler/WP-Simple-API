<?php



class SearchAPI {

  public function searchPosts($table_prefix, $searchKey) {
      $mysqli = dbConnect();

      $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count FROM".$table_prefix."posts
      WHERE post_title LIKE ". $searchKey . "
      ORDER BY ".$table_prefix."posts.post_date
      DESC LIMIT 0, 10"
    
    //  $mysqli->query($sql);

    //  $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count FROM ".$table_prefix."posts WHERE `ID` = ($post_id)";
      if ($result = $mysqli->query($sql)) {
          while ($row = $result->fetch_object()) {
              $finalResult[] = $row;
          }
      }
      $mysqli->close();

      return $finalResult;
  }

}


?>
