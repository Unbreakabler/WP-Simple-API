<?php



class ObitsAPI {

    public function getPostByType() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if ($data['type'] == '/byid') {
            $res = $this->getObitsByID();
        } else if ($data['type'] == '/obprev') {
            $res = $this->getPreviousObitByID();
        } else if ($data['type'] == '/obnext'){
            $res = $this->getNextObitByID();
        } else if ($data['type'] == 'category'){
            $res = $this->getObits();
        }
        return $res;
    }

    public function getObits() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $count = (isset($data['count']) ? $data['count'] : 5);
        $index = (isset($data['index']) ? $data['index'] : 0);

        $mysqli = dbConnect();

        if ($index != 0) {
            $sql = "SELECT ID,post_author,post_date,post_content,post_title
            FROM ".TABLE_PREFIX."posts
            WHERE post_status = 'publish'
            AND ".TABLE_PREFIX."posts.id < '$index'
            AND post_type = 'obituaries'
            ORDER BY ".TABLE_PREFIX."posts.post_date
            DESC LIMIT 0, $count";
        } else {
            $sql = "SELECT ID,post_author,post_date,post_content,post_title
            FROM ".TABLE_PREFIX."posts
            WHERE post_status = 'publish'
            AND ".TABLE_PREFIX."posts.id > '$index'
            AND post_type = 'obituaries'
            ORDER BY ".TABLE_PREFIX."posts.post_date
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

    public function getObitsByID() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $post_id = $data['post_id'];
        $mysqli = dbConnect();

        //$indexDate = $this->findIndexDate(TABLE_PREFIX, $post_id, $mysqli);

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
                FROM ".TABLE_PREFIX."posts WHERE
                ".TABLE_PREFIX."posts.post_type = 'obituaries'
                AND ".TABLE_PREFIX."posts.post_status = 'publish'
                AND ".TABLE_PREFIX."posts.id = $post_id";
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

  public function getPreviousObitByID() {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $post_id = $data['post_id'];
      $mysqli = dbConnect();

      $indexDate = $this->findIndexDate($post_id, $mysqli);

      $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
              FROM ".TABLE_PREFIX."posts WHERE
              ".TABLE_PREFIX."posts.post_type = 'obituaries'
              AND (".TABLE_PREFIX."posts.post_status = 'publish'
              OR ".TABLE_PREFIX."posts.post_status = 'expired'
              OR ".TABLE_PREFIX."posts.post_status = 'private')
              AND ".TABLE_PREFIX."posts.post_date > '$indexDate'
              ORDER BY ".TABLE_PREFIX."posts.post_date
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


  public function getNextObitByID() {
      $json = file_get_contents('php://input');
      $data = json_decode($json, true);

      $post_id = $data['post_id'];
      $mysqli = dbConnect();

      $indexDate = $this->findIndexDate($post_id, $mysqli);

      $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
              FROM ".TABLE_PREFIX."posts  WHERE
              ".TABLE_PREFIX."posts.post_type = 'obituaries'
              AND (".TABLE_PREFIX."posts.post_status = 'publish'
              OR ".TABLE_PREFIX."posts.post_status = 'expired'
              OR ".TABLE_PREFIX."posts.post_status = 'private')
              AND ".TABLE_PREFIX."posts.post_date < '$indexDate'
              ORDER BY ".TABLE_PREFIX."posts.post_date
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


  public function setIndexDate($index, $mysqli) {
      $indexDate = new DateTime();
      $indexDate = $indexDate->format( 'Y-m-d H:i:s');

      if ($index !== 0) {
          $indexDate = $this->findIndexDate($index, $mysqli);
      }
      return $indexDate;
  }

  private function findIndexDate($index, $mysqli) {

      $sql = "SELECT ".TABLE_PREFIX."posts.post_date FROM ".TABLE_PREFIX."posts WHERE ".TABLE_PREFIX."posts.ID = $index";
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
