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

class PostAPI {

    public function setIndexDate($index, $mysqli) {
        $indexDate = new DateTime();
        $indexDate = $indexDate->format( 'Y-m-d H:i:s');

        if ($index !== 0) {
            $indexDate = $this->findIndexDate($table_prefix, $index, $mysqli);
        }
        return $indexDate;
    }

    public function getRecentPostsByCategory($table_prefix, $term_id = 2, $count = 5, $index = 0) {
        $mysqli = dbConnect();

        $indexDate = $this->setIndexDate($index, $mysqli);

        $sql = "SELECT SQL_CALC_FOUND_ROWS ".$table_prefix."posts.ID
                FROM ".$table_prefix."posts INNER JOIN ".$table_prefix."term_relationships ON
                (".$table_prefix."posts.ID = ".$table_prefix."term_relationships.object_id) WHERE 1=1
                AND ( ".$table_prefix."term_relationships.term_taxonomy_id IN ($term_id) )
                AND ".$table_prefix."posts.post_type = 'post'
                AND (".$table_prefix."posts.post_status = 'publish'
                OR ".$table_prefix."posts.post_status = 'expired'
                OR ".$table_prefix."posts.post_status = 'private')
                AND ".$table_prefix."posts.post_date < '$indexDate'
                GROUP BY ".$table_prefix."posts.ID ORDER BY ".$table_prefix."posts.post_date
                DESC LIMIT 0, $count";

        if ($result = $mysqli->query($sql)) {
            while($row = $result->fetch_object()) {
                $resultArray[] = $row;
            }
        } else {
            return 'Select Statement Failed';
        }

        $mysqli->close();
        return $resultArray;
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

    public function getPostListByID($table_prefix, $ids) {
        $mysqli = dbConnect();
        $idString = '';

        foreach ($ids as $id) {
            $idString .= $id->ID . ',';
        }

        $idString = rtrim($idString, ",");
        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_status FROM ".$table_prefix."posts WHERE `ID` IN ($idString)";

        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {
                $resultField[] = $row;
            }
        }
        $mysqli->close();
        return $resultField;
    }

    public function getPostByID($table_prefix, $post_id) {
        $mysqli = dbConnect();

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_status FROM ".$table_prefix."posts WHERE `ID` = ($post_id)";
        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {
                $finalResult[] = $row;
            }
        }
        $mysqli->close();
        return $finalResult;
    }

    public function getNextPostByID($table_prefix, $post_id, $term_id) {
        $mysqli = dbConnect();

        $indexDate = $this->findIndexDate($table_prefix, $post_id, $mysqli);

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_status
                FROM ".$table_prefix."posts INNER JOIN ".$table_prefix."term_relationships ON
                (".$table_prefix."posts.ID = ".$table_prefix."term_relationships.object_id) WHERE 1=1
                AND ( ".$table_prefix."term_relationships.term_taxonomy_id IN ($term_id) )
                AND ".$table_prefix."posts.post_type = 'post'
                AND (".$table_prefix."posts.post_status = 'publish'
                OR ".$table_prefix."posts.post_status = 'expired'
                OR ".$table_prefix."posts.post_status = 'private')
                AND ".$table_prefix."posts.post_date > '$indexDate'
                GROUP BY ".$table_prefix."posts.ID ORDER BY ".$table_prefix."posts.post_date
                ASC LIMIT 0, 1";

        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {
                $finalResult[] = $row;
            }
        }
        $mysqli->close();
        return $finalResult;
    }

    public function getPreviousPostByID($table_prefix, $post_id, $term_id) {
        $mysqli = dbConnect();

        $indexDate = $this->findIndexDate($table_prefix, $post_id, $mysqli);

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_status
                FROM ".$table_prefix."posts INNER JOIN ".$table_prefix."term_relationships ON
                (".$table_prefix."posts.ID = ".$table_prefix."term_relationships.object_id) WHERE 1=1
                AND ( ".$table_prefix."term_relationships.term_taxonomy_id IN ($term_id) )
                AND ".$table_prefix."posts.post_type = 'post'
                AND (".$table_prefix."posts.post_status = 'publish'
                OR ".$table_prefix."posts.post_status = 'expired'
                OR ".$table_prefix."posts.post_status = 'private')
                AND ".$table_prefix."posts.post_date < '$indexDate'
                GROUP BY ".$table_prefix."posts.ID ORDER BY ".$table_prefix."posts.post_date
                DESC LIMIT 0, 1";

        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {
                $finalResult[] = $row;
            }
        }
        $mysqli->close();
        return $finalResult;
    }

    public function getPostMetaData($table_prefix, $posts) {
        $mysqli = dbConnect();

        foreach ($posts as $post) {
            //Appends the header image to each post object
            $sql = "SELECT * FROM ".$table_prefix."postmeta WHERE post_id = $post->ID AND meta_key IN ('tie_views','_thumbnail_id')";

            //var_dump($sql);
            if ($result = $mysqli->query($sql)) {
                while ($row = $result->fetch_object()) {
                    if ($row->meta_key == '_thumbnail_id') {
                        //$post->thumbnailID = $row->meta_value;
                        $sql = "SELECT * FROM `".$table_prefix."posts` WHERE `ID` = $row->meta_value AND `post_type` LIKE 'attachment'";
                        if ($newresult = $mysqli->query($sql)) {
                            while ($row = $newresult->fetch_object()) {
                                $post->thumbnailURI = $row->guid;
                            }
                        }
                    } else {
                        //var_dump($row->meta_value);
                        $post->views = $row->meta_value;
                    }
                }
            }
            //Appends the real name of the author to each post object
            $sql = "SELECT `display_name`FROM `".$table_prefix."users` WHERE `ID` = $post->post_author";
            if ($result = $mysqli->query($sql)) {
                $obj = $result->fetch_object();
                $post->author_name = $obj->display_name;
            }

            if (!isset($post->thumbnailURI)) {
                $post->thumbnailURI = DEFAULT_POST_IMAGE;
            }
        }

        //var_dump($posts);
        $mysqli->close();
        return $posts;
        //var_dump($thumbnailID);
    }
}
?>
