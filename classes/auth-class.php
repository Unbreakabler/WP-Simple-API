<?php
class AuthAPI {
    public function authorizeToken() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (isset($data['auth_token']) != SECURE_TOKEN) {
            exit("Authentication failed");
        }
        return;
    }
}

?>
