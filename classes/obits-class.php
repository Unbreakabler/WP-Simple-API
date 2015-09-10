<?php



class ObitsAPI {

  public function getObits($table_prefix, $count = 10, $index = 0) {

      $mysqli = dbConnect();

      if ($index != 0) {
        $sql = "SELECT ID,post_author,post_date,post_content,post_title
        FROM ".$table_prefix."posts
        WHERE post_status = 'publish'
        AND ".$table_prefix."posts.id < '$index'
        AND post_type = 'obituaries'
        ORDER BY ".$table_prefix."posts.post_date
        DESC LIMIT 0, $count";
      } else {
        $sql = "SELECT ID,post_author,post_date,post_content,post_title
        FROM ".$table_prefix."posts
        WHERE post_status = 'publish'
        AND ".$table_prefix."posts.id > '$index'
        AND post_type = 'obituaries'
        ORDER BY ".$table_prefix."posts.post_date
        DESC LIMIT 0, $count";
      }

      //echo $sql;
      if ($result = $mysqli->query($sql)) {
          $count = 0;
          while ($row = $result->fetch_object()) {
              $finalResult[] = $row;
              $count++;
          }
          if ($count == 0) {
              $post_title['post_title'] = "No Results Found";
              $post_title['ID'] = "0";
              $finalResult[] = $post_title;
          }
      } else {
          $finalResult = "Nothing Found";
      }
      $mysqli->close();

      return $finalResult;
  }

  public function getObitsByID($table_prefix, $post_id) {
      $mysqli = dbConnect();

      //$indexDate = $this->findIndexDate($table_prefix, $post_id, $mysqli);

      $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
              FROM ".$table_prefix."posts WHERE
              ".$table_prefix."posts.post_type = 'obituaries'
              AND ".$table_prefix."posts.post_status = 'publish'
              AND ".$table_prefix."posts.id = $post_id";
      //echo $sql;
      if ($result = $mysqli->query($sql)) {
          while ($row = $result->fetch_object()) {
              $finalResult[] = $row;
          }
      } else {

        $finalResult = "Nothing Found";

      }
      $mysqli->close();
      // if (!isset($finalResult)) {
      //     $finalResult = array("error" => true, "MessageFormatter" => null);
      // }
      return $finalResult;
  }

  public function getPreviousObitByID($table_prefix, $post_id) {
      $mysqli = dbConnect();

      $indexDate = $this->findIndexDate($table_prefix, $post_id, $mysqli);

      $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
              FROM ".$table_prefix."posts WHERE
              ".$table_prefix."posts.post_type = 'obituaries'
              AND (".$table_prefix."posts.post_status = 'publish'
              OR ".$table_prefix."posts.post_status = 'expired'
              OR ".$table_prefix."posts.post_status = 'private')
              AND ".$table_prefix."posts.post_date > '$indexDate'
              ORDER BY ".$table_prefix."posts.post_date
              ASC LIMIT 0, 1";
      //echo $sql;
      if ($result = $mysqli->query($sql)) {
          while ($row = $result->fetch_object()) {
              $finalResult[] = $row;
          }
      } else {

        $finalResult = "Nothing Found";

      }
      $mysqli->close();
      // if (!isset($finalResult)) {
      //     $finalResult = array("error" => true, "MessageFormatter" => null);
      // }
      return $finalResult;
  }


  public function getNextObitByID($table_prefix, $post_id) {
      $mysqli = dbConnect();

      $indexDate = $this->findIndexDate($table_prefix, $post_id, $mysqli);

      $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
              FROM ".$table_prefix."posts  WHERE
              ".$table_prefix."posts.post_type = 'obituaries'
              AND (".$table_prefix."posts.post_status = 'publish'
              OR ".$table_prefix."posts.post_status = 'expired'
              OR ".$table_prefix."posts.post_status = 'private')
              AND ".$table_prefix."posts.post_date < '$indexDate'
              ORDER BY ".$table_prefix."posts.post_date
              DESC LIMIT 0, 1";
    //echo $sql;

      if ($result = $mysqli->query($sql)) {
          while ($row = $result->fetch_object()) {
              $finalResult[] = $row;
          }
      } else {
        $finalResult = "Nothing Found";

      }
      $mysqli->close();
      return $finalResult;
  }


  public function setIndexDate($table_prefix, $index, $mysqli) {
      $indexDate = new DateTime();
      $indexDate = $indexDate->format( 'Y-m-d H:i:s');

      if ($index !== 0) {
          $indexDate = $this->findIndexDate($table_prefix, $index, $mysqli);
      }
      return $indexDate;
  }

  private function findIndexDate($table_prefix, $index, $mysqli) {

      $sql = "SELECT ".$table_prefix."posts.post_date FROM ".$table_prefix."posts WHERE ".$table_prefix."posts.ID = $index";
      $resultField = 'ID NOT FOUND';

      if ($result = $mysqli->query($sql)) {
          while($row = $result->fetch_object()) {
              $resultField = $row->post_date;
          }
      }
      return $resultField ;
  }


} // ends api
?>
