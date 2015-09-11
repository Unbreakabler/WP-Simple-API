<?php
class UserAPI {

    private function getUserFromURL($url, $token) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,
            $url . $token . '&fields=id,first_name,last_name,email&format=json'
        );
        $content = curl_exec($ch);
        return json_decode($content);
    }

    private function getUserFromServer($data) {
        $password = $data['password'];
        $username = $data['username'];
        $res = new stdClass();
        $user = get_user_by( 'login', $username);
        // If the username doesn't work, check to make sure they didn't try to login with their email instead of username
        if (!$user) {
            $user = get_user_by('email', $username);
        }
        if ( $user && wp_check_password( $password, $user->data->user_pass, $user->ID) ) {
            @$res->data->display_name = $user->data->display_name;
            @$res->data->ID = $user->data->ID;
            @$res->data->user_email = $user->data->user_email;
            @$res->data->user_nicename = $user->data->user_nicename;
            return $res;
        } else {
            if ($user) {
                @$res->data->error->password = true;
                @$res->data->error->message = 'Password incorrect';
            } else {
                @$res->data->error->username = true;
                @$res->data->error->message = 'Username does not exist';
            }
            return $res;
        }
    }

    private function randomPasswordGen() {
        $pass = bin2hex(openssl_random_pseudo_bytes(6));
        $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
        $salt = base64_encode($salt);
        $salt = str_replace('+', '.', $salt);
        $hash = crypt($pass, '$P$B'.$salt.'$');
        return $hash;
    }

    public function userSignUp($table_prefix) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $mysqli = dbConnect();
        $user = new stdClass();
        $res = new stdClass();

        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        $user->pass = $data['pass'];
        $user->email = $data['email'];

        // get_user_by login and email, if they exists do not allow the account to be created.
        $creation = $this->createNewUser($mysqli, $table_prefix, $user, null);
        unset($user->pass);
        if (!$creation) {
            $user = get_user_by('email', $user->email);
            return $user->data;
        } else {
            return $creation->data;
        }
    }

    private function createNewUser($mysqli, $table_prefix, $user, $service_db_id) {
        $userLogin = '';
        if (isset($user->first_name)) {
            $userLogin .= strtolower($user->first_name);
        }
        if (isset($user->last_name)) {
            $userLogin .= strtolower($user->last_name);
        }

        if (!$userLogin) {
            $userLogin = $user->email;
        }

        $res = new stdClass();

        // if username or email is already in use, deny the creation
        // TODO: Return detailed error messages to display client side
        if (get_user_by('login', $userLogin) || get_user_by('slug', $userLogin)) {

            // This should never happen
            if (get_user_by('email', $user->email)) {
                @$res->data->error->email = true;
                @$res->data->error->message = 'This email is already registered';
                return $res;
            }

            $i = 1;
            $newLogin = $userLogin . $i;
            while (get_user_by('login', $newLogin)) {
                $i++;
                $newLogin = $userLogin . $i;
            }
            $userLogin = $newLogin;
        }

        if ($user->pass) {
            $pass = wp_hash_password($user->pass);
        } else {
            // Create random password for users that login via facebook
            $pass = wp_hash_password($this->randomPasswordGen());
        }
        $time = date('Y-m-d H:i:s');

        $sql = "INSERT INTO `".$table_prefix."users` (user_login,user_pass,user_nicename,user_email,user_registered,display_name)
        VALUES ('$userLogin','$pass','$userLogin','$user->email','$time','$user->first_name $user->last_name')";
        //var_dump($sql);

        $mysqli->query($sql);

        $NEWUSERID = $mysqli->insert_id;

        $capabilities = $table_prefix.'capabilities';
        $user_level = $table_prefix.'user_level';
        if ($service_db_id) {
            $social_signup = 1;
        } else {
            $social_signup = 0;
        }
        // get created user_id in order to store meta data

        $sql = "INSERT INTO `".$table_prefix."usermeta` (user_id,meta_key,meta_value) VALUES
        ($NEWUSERID,'nickname','$userLogin'),
        ($NEWUSERID,'first_name','$user->first_name $user->last_name'),
        ($NEWUSERID,'last_name',''),
        ($NEWUSERID,'description',''),
        ($NEWUSERID,'rich_editing','true'),
        ($NEWUSERID,'comment_shortcuts','false'),
        ($NEWUSERID,'admin_color','fresh'),
        ($NEWUSERID,'use_ssl','0'),
        ($NEWUSERID,'show_admin_bar_front','true'),
        ($NEWUSERID,'$capabilities','a:1:{s:10:\"subscriber\";b:1;}'),
        ($NEWUSERID,'$user_level','0'),
        ($NEWUSERID,'xoouser_ultra_social_signup','$social_signup'),
        ($NEWUSERID,'$service_db_id','$NEWUSERID'),
        ($NEWUSERID,'usersutlra_account_status','active'),
        ($NEWUSERID,'session_tokens',''),
        ($NEWUSERID,'uultra_last_login','$time')";

        $res = $mysqli->query($sql);

        return;
    }

    // Get user information from the database based on the user information returned from facebook graph
    private function getUserFromFacebookInfo($user, $service_db_id, $table_prefix) {
        $mysqli = dbConnect();

        $sql = "SELECT ID,user_nicename,display_name,user_email FROM `".$table_prefix."users` WHERE `user_email` = '$user->email'";

        if ($result = $mysqli->query($sql)) {
            if ($res = $result->fetch_object()) {
                unset($res->user_pass);
                // If user exists, find if the user has logged in with the current service before
                $sql = "SELECT meta_value FROM `".$table_prefix."usermeta` WHERE `meta_key` = '$service_db_id' AND `user_id` = $res->ID";
                // if they haven't, add it to the usermeta table
                if ($result = $mysqli->query($sql)) {
                    if (!$result->fetch_object()) {
                        $sql = "INSERT INTO `".$table_prefix."usermeta` (user_id,meta_key,meta_value)
                        VALUES ('$res->ID','$service_db_id','$user->id')";
                        $result = $mysqli->query($sql);
                    }
                }

            } else {
                // if email doesn't match an existing account, create new account
                $newUser = $this->createNewUser($mysqli, $table_prefix, $user, $service_db_id);
                if ($newUser) {
                    return $newUser;
                }
                $sql = "SELECT ID,user_nicename,display_name FROM `".$table_prefix."users` WHERE `user_email` = '$user->email'";
                $result = $mysqli->query($sql);
                $res = $result->fetch_object();
                unset($res->user_pass);
            }
        }
        $res->picture = $user->picture;
        return $res;
    }

    public function getUserByToken($table_prefix) {

        // switch to query different restful APIs based on login_service the users access token came from
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $service = $data['login_service'];

        switch ($service) {
            case 'facebook':
                $token = $data['access_token'];
                $user = $this->getUserFromURL('https://graph.facebook.com/v2.2/me/?access_token=', $token);
                $user->picture = 'https://graph.facebook.com/' . $user->id . '/picture?type=normal';
                $service_db_id = 'xoouser_utlra_facebook_id';
                $res = $this->getUserFromFacebookInfo($user, $service_db_id, $table_prefix);
                return $res;
                break;
            case 'login':
                $user = $this->getUserFromServer($data);
                return $user->data;
                break;
        }
    }

}
?>
