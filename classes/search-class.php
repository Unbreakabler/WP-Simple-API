<?php

class SearchAPI {
    public function searchPosts() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $searchKey = (isset($data['search_key']) ? $data['search_key'] : 0);
        $count = (isset($data['count']) ? $data['count'] : 10);
        $index = (isset($data['index']) ? $data['index'] : 0);

        $mysqli = dbConnect();
        $indexDate = $this->setIndexDate($index, $mysqli);

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
        FROM ".TABLE_PREFIX."posts
        WHERE post_status = 'publish'
        AND ".TABLE_PREFIX."posts.post_date < '$indexDate'
        AND post_title LIKE '%". $searchKey . "%'
        ORDER BY ".TABLE_PREFIX."posts.post_date
        DESC LIMIT 0, $count";

        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {
                $finalResult[] = $row;
            }
        }

        if (!isset($finalResult)) {
            $finalResult[0]['post_title'] = "No Results Found";
            $finalResult[0]['ID'] = "0";
        }

        $mysqli->close();

        return $finalResult;
    }

    public function getSearchRecordCount($search_result) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $mysqli = dbConnect();

        $searchKey = (isset($data['search_key']) ? $data['search_key'] : 0);
        $index = (isset($data['index']) ? $data['index'] : 0);

        $indexDate = $this->setIndexDate($index, $mysqli);
        $sql = "SELECT count(*) as count
        FROM ".TABLE_PREFIX."posts
        WHERE post_status = 'publish'
        AND ".TABLE_PREFIX."posts.post_date < '$indexDate'
        AND post_title LIKE '%". $searchKey . "%'";

        $result = $mysqli->query($sql);

        $count = $result->fetch_object();
        $count->search = $search_result;
        $mysqli->close();

        return $count;
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
}
?>
