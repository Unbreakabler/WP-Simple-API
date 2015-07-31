<?php

class UserAPI {

    private function getUserInfo($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,
            $url . $_GET['access_token'] . '&fields=id,first_name,last_name,email&format=json'
        );
        $content = curl_exec($ch);
        return json_decode($content);
    }

    private function randomPasswordGen() {
        $pass = bin2hex(openssl_random_pseudo_bytes(6));
        $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
        $salt = base64_encode($salt);
        $salt = str_replace('+', '.', $salt);
        $hash = crypt($pass, '$P$B'.$salt.'$');
        return $hash;
    }

    private function createNewUser($mysqli, $table_prefix, $user, $service_db_id) {
        $displayName = '';
        if (isset($user->first_name)) {
            $displayName .= strtolower($user->first_name);
        }
        if (isset($user->last_name)) {
            $displayName .= strtolower($user->last_name);
        }

        // FIXME: Are there edge cases where the facebook API doesn't return a first or last name?
        if (!$displayName) {
            return;
        }


        //maximum length for a new accounts display name
        $displayName = substr($displayName, 0, 32);

        $pass = $this->randomPasswordGen();
        $time = date('Y-m-d H:i:s');

        $sql = "INSERT INTO `".$table_prefix."users` (user_login,user_pass,user_nicename,user_email,user_registered,display_name)
        VALUES ('$displayName','$pass','$displayName','$user->email','$time','$user->first_name $user->last_name')";
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
        ($NEWUSERID,'nickname','$displayName'),
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
        ($NEWUSERID,'$service_db_id','$user->id'),
        ($NEWUSERID,'usersutlra_account_status','active'),
        ($NEWUSERID,'session_tokens',''),
        ($NEWUSERID,'uultra_last_login','$time')";

        $res = $mysqli->query($sql);

        return 0;
    }

    public function getUserByToken($table_prefix) {

        // switch to query different restful APIs based on login_service the users access token came from
        $service = $_GET['login_service'];

        switch ($service) {
            case 'facebook':
                $user = $this->getUserInfo('https://graph.facebook.com/v2.2/me/?access_token=');
                $user->picture = 'https://graph.facebook.com/' . $user->id . '/picture?type=normal';
                $service_db_id = 'xoouser_utlra_facebook_id';
                # code...
                break;

            case 'twitter':
                # code...
                break;

            case 'google':
                # $user = $this->getUserInfo('')
                # $user = $this->getUserInfo('https://accounts.google.com/o/oauth2/auth?response_type=token&scope=profile&redirect_uri=http://localhost/callback&client_id=396458382686-pg2fkuho35m7ck9bdmbo238glbq580gd.apps.googleusercontent.com');
                # code...
                break;

            default:
                # code...
                break;
        }

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
                var_dump('asdfasdf');
                // if email doesn't match an existing account, create new account
                $this->createNewUser($mysqli, $table_prefix, $user, $service_db_id);
                $sql = "SELECT ID,user_nicename,display_name FROM `".$table_prefix."users` WHERE `user_email` = '$user->email'";
                $result = $mysqli->query($sql);
                $res = $result->fetch_object();
                unset($res->user_pass);
            }
        }
        $res->picture = $user->picture;
        return $res;
    }

}
?>
