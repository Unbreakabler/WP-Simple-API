<?php



class SearchAPI {

  public function searchPosts($table_prefix, $searchKey, $count = 10, $index = 0) {
      //header('Access-Control-Allow-Origin', '*');

      // parse search string:

      $new_string = $this->cleanSearchString($searchKey);

      $mysqli = dbConnect();
      $indexDate = $this->setIndexDate($table_prefix, $index, $mysqli);

      $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
      FROM ".$table_prefix."posts
      WHERE post_status = 'publish'
      AND ".$table_prefix."posts.post_date < '$indexDate'
      AND post_title LIKE '". $new_string . "'
      AND post_type = 'post'
      ORDER BY ".$table_prefix."posts.post_date
      DESC LIMIT 0, $count";
 //echo $sql;
    //  $mysqli->query($sql);

    //  $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count FROM ".$table_prefix."posts WHERE `ID` = ($post_id)";
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

  public function getSearchRecordCount($table_prefix, $searchKey, $search_result, $index = 0) {
    $mysqli = dbConnect();
    $indexDate = $this->setIndexDate($table_prefix, $index, $mysqli);

    $new_string = $this->cleanSearchString($searchKey);


    $sql = "SELECT count(*) as count
    FROM ".$table_prefix."posts
    WHERE post_status = 'publish'
    AND ".$table_prefix."posts.post_date < '$indexDate'
    AND post_title LIKE '". $new_string . "'
    AND post_type = 'post'";


    $result = $mysqli->query($sql);

    $count = $result->fetch_object();
    $count->search = $search_result;
    $mysqli->close();

    return $count;

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

  private function cleanSearchString($search_string) {

    // Strip HTML Tags
    $clear = strip_tags($search_string);
    // Clean up things like &amp;
    $clear = html_entity_decode($clear);
    // Replace single quote with Double Single Quote
    $clear = str_replace("'", "''", $clear);
    // Strip out any url-encoded stuff
    $clear = urldecode($clear);

    $remove_words = array(
                        "kamloops",
                        " of ",
                        " a ",
                        " in ",
                        " it ",
                        " is ",
                        " the ",
                        " for ",
                        " he ",
                        " she ",
                        " him ",
                        " her ",
                        " on ",
                        " be ",
                        " to ",
                        " had ",
                        " has ",
                        " and ",
                        " or ",
                        " if ",
                        " have ",
                        " all ",
                        " its ",
                        " an ",
                        " but ",
                        " into ",
                        " no ",
                        " not ",
                        " such ",
                        " that ",
                        " their ",
                        " then ",
                        " there ",
                        " these ",
                        " they ",
                        " this ",
                        " was ",
                        " will ",
                        " with "
                      );
    // Replace non-AlNum characters with space
    $clear = str_replace($remove_words, ' ', $clear);
    //$clear = preg_replace('/[^A-Za-z0-9]/', ' ', $clear);
    // Replace Multiple spaces with single space
    $clear = preg_replace('/ +/', ' ', $clear);
    // Trim the string of leading/trailing space
    $clear = trim($clear);


    $new_string = "%".$clear."%";

    $new_string = str_replace(" ", "% %", $new_string);

    return $new_string;



  }


}


?>
