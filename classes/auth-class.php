<?php
class AuthAPI {
    public function authorizeToken() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (isset($data['auth_token'])) {
            if ($data['auth_token'] != SECURE_TOKEN) {
                exit('Authentication token mismatch');
            }
        }
        if (!isset($data['auth_token'])) {
            exit('No token present with request');
        }
        return;
    }
}

?>
