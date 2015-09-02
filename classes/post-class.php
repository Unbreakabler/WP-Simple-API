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

class PostAPI {

    public function getGalleryMeta($table_prefix) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $mysqli = dbConnect();

        $idString = '' ;
        foreach ($data['ids'] as $id) {
            $idString .= $id . ',';
        }
        $idString = rtrim($idString, ',');
        $sql = "SELECT meta_value FROM `".$table_prefix."postmeta` WHERE meta_key = '_wp_attached_file' AND post_id IN ($idString)";
        if ($result = $mysqli->query($sql)) {
            while ($row = $result->fetch_object()) {
                $res[] = $row;
            }
        }

        $mysqli->close();
        return $res;
    }

    public function buildGalleryLinks ($links) {
        $resArr = [];
        foreach ($links as $urlEnd) {
            $urlEnd->fullsize = SITE_URL . '/wp-content/uploads/' . $urlEnd->meta_value;

            $path_parts = pathinfo($urlEnd->fullsize);
            $urlEnd->scaled = $path_parts['dirname'] . '/' . $path_parts['filename'] . '-300x160.' . $path_parts['extension'];

            // FIXME: Reanble file checking, disabled until I can improve the performance
            // $exists = $this->remoteFileExists($urlEnd->scaled);
            // if (!$exists) {
            //     $urlEnd->scaled = $urlEnd->fullsize;
            // }

        }
        return $links;
    }

    function remoteFileExists($url) {
        $curl = curl_init($url);

        //don't fetch the actual page, you only want to check the connection is ok
        curl_setopt($curl, CURLOPT_NOBODY, true);

        //do request
        $result = curl_exec($curl);

        $ret = false;

        //if request did not fail
        if ($result !== false) {
            //if request was ok, check response code
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($statusCode == 200) {
                $ret = true;
            }
        }

        curl_close($curl);

        return $ret;
    }

    public function setIndexDate($table_prefix, $index, $mysqli) {
        $indexDate = new DateTime();
        $indexDate = $indexDate->format( 'Y-m-d H:i:s');

        if ($index !== 0) {
            $indexDate = $this->findIndexDate($table_prefix, $index, $mysqli);
        }
        return $indexDate;
    }

    public function getRecentPostsByCategory($table_prefix, $term_id = 2, $count = 5, $index = 0) {
        $mysqli = dbConnect();

        $indexDate = $this->setIndexDate($table_prefix, $index, $mysqli);

        $sql = "SELECT ".$table_prefix."posts.ID
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
        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count FROM ".$table_prefix."posts WHERE `ID` IN ($idString) ORDER BY ".$table_prefix."posts.post_date";
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

        $sql = "UPDATE ".$table_prefix."postmeta
        SET meta_value = meta_value + 1
        WHERE post_id = $post_id
        AND meta_key IN ('".VIEW_METAKEY."')";
        $mysqli->query($sql);

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count FROM ".$table_prefix."posts WHERE `ID` = ($post_id)";
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

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
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
        // if (!isset($finalResult)) {
        //     $finalResult = array("error" => true, "MessageFormatter" => null);
        // }
        return $finalResult;
    }

    // TODO: If previous post doesn't exist return an error string instead of causing application Error
    public function getNextPostByID($table_prefix, $post_id, $term_id) {
        $mysqli = dbConnect();

        $indexDate = $this->findIndexDate($table_prefix, $post_id, $mysqli);

        $sql = "SELECT ID,post_author,post_date,post_content,post_title,comment_count
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

    // FIXME: Getting called 5 times on initial page load (view count is increasing by 5 when called from app)
    public function getPostMetaData($table_prefix, $posts, $category_id = 0) {
        $mysqli = dbConnect();

        foreach ($posts as $post) {
            $newDatetime = new DateTime($post->post_date);
            $newDatetime = $newDatetime->format('F j, Y, g:i a');

            $post->post_date = $newDatetime;
            //Appends the header image to each post object

            $sql = "SELECT * FROM ".$table_prefix."postmeta
                    WHERE post_id = $post->ID
                    AND meta_key IN ('".VIEW_METAKEY."','_thumbnail_id','CODE1')";

            //var_dump($sql);
            if ($result = $mysqli->query($sql)) {
                while ($row = $result->fetch_object()) {
                    //var_dump($row);
                    if ($row->meta_key == '_thumbnail_id') {
                        //$post->thumbnailID = $row->meta_value;
                        $sql = "SELECT * FROM `".$table_prefix."posts` WHERE `ID` = $row->meta_value AND `post_type` LIKE 'attachment'";
                        if ($newresult = $mysqli->query($sql)) {
                            while ($row = $newresult->fetch_object()) {
                                $path_parts = pathinfo($row->guid);
                                $post->thumbnailURI = $path_parts['dirname'] . '/' . $path_parts['filename'] . '-300x160.' . $path_parts['extension'];
                                $post->thumbnailURI70 = $path_parts['dirname'] . '/' . $path_parts['filename'] . '-70x70.' . $path_parts['extension'];
                                $post->thumbnailURI150 = $path_parts['dirname'] . '/' . $path_parts['filename'] . '-150x150.' . $path_parts['extension'];
                            }
                        }
                    } else if ($row->meta_key == VIEW_METAKEY) {
                        $post->views = $row->meta_value;
                    } else {
                        $post->code = $row->meta_value;
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
                $post->thumbnailURI70 = DEFAULT_POST_IMAGE70;
                $post->thumbnailURI150 = DEFAULT_POST_IMAGE150;
            }
            if ($category_id != 0) {
                $sql = "SELECT term_taxonomy_id FROM `".$table_prefix."term_relationships` WHERE `object_id` = $post->ID";
                if ($result = $mysqli->query($sql)) {
                    while ($row = $result->fetch_object()) {
                        if (($row->term_taxonomy_id != $category_id) && ($row->term_taxonomy_id != FEATURED_TERM_ID)) {
                            $post->categories[] = $row->term_taxonomy_id;
                        }
                    }
                }

                $catString = '';
                if (isset($post->categories)) {
                    foreach ($post->categories as $cat) {
                        $catString .= $cat . ',';
                    }
                    $catString = rtrim($catString, ",");

                    $sql = "SELECT term_id FROM `".$table_prefix."term_taxonomy` WHERE `term_taxonomy_id` IN ($catString)";
                    //echo $sql;
                    if ($result = $mysqli->query($sql)) {
                        while ($row = $result->fetch_object()) {
                            $post->terms[] = $row->term_id;
                        }
                    }
                    if (($post->terms[0] == DEFAULT_TERM_ID) && (isset($post->terms[1]))) {
                        $selectedTerm = $post->terms[1];
                    } else {
                        $selectedTerm = $post->terms[0];
                    }
                    $sql = "SELECT name FROM `".$table_prefix."terms` WHERE term_id = $selectedTerm";
                    if ($result = $mysqli->query($sql)) {
                        while ($row = $result->fetch_object()) {
                            $post->category_name = $row->name;
                        }
                    }
                    unset($post->terms);
                    unset($post->categories);
                }
            }
        }

        $mysqli->close();
        return $posts;
    }
}
?>
