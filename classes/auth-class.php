<?php

class AuthAPI {

    public function authorizeToken() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if ($data['auth_token'] != SECURE_TOKEN) {
            die();
        }
        return;
    }

}

?>
