<?php

class requestSender {
    private function jsonErrorTesting() {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                echo ' - No errors';
            break;
            case JSON_ERROR_DEPTH:
                echo ' - Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                echo ' - Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
                echo ' - Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
                echo ' - Syntax error, malformed JSON';
            break;
            case JSON_ERROR_UTF8:
                echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
            default:
                echo ' - Unknown error';
            break;
        }
    }

    public function sendJSONResponse($app, $responseBody) {
        $response = $app->response();
        // Allow access to everyone for initial testing purposes
        // TODO: Implemention authentication protocol so the API can only be accessed by the app
        $response->header('Access-Control-Allow-Origin', '*');

        //$this->jsonErrorTesting();

        // TODO: Implement better error handling
        // 204 No Content -> Currently using if there are no comments on current article
        if ($responseBody == 204) {
            return;
        } else {
            $response->write(json_encode($responseBody, JSON_HEX_QUOT | JSON_HEX_TAG));
        }
    }
}
?>
