<?php
class SearchAPI {
    public function searchPosts() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $searchKey = (isset($data['search_key']) ? $data['search_key'] : 0);
        $count = (isset($data['count']) ? $data['count'] : 10);
        $index = (isset($data['index']) ? $data['index'] : 0);

        $new_string = $this->cleanSearchString($searchKey);

        $mysqli = dbConnect();
        $indexDate = $this->setIndexDate($index, $mysqli);

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
        FROM ".TABLE_PREFIX."posts
        WHERE post_status = 'publish'
        AND ".TABLE_PREFIX."posts.post_date < '$indexDate'
        AND post_title LIKE '". $new_string . "'
        AND post_type = 'post'
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
        $new_string = $this->cleanSearchString($searchKey);

        $sql = "SELECT count(*) as count
        FROM ".TABLE_PREFIX."posts
        WHERE post_status = 'publish'
        AND ".TABLE_PREFIX."posts.post_date < '$indexDate'
        AND post_title LIKE '". $new_string . "'
        AND post_type = 'post'";

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
