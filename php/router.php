<?php 
require_once("error.php");
require_once("api.php");
enum Referer {
    case Login;
    case Register;
    case Delete;
    case AggregateContent;
};

class Router {
    private $request;
    private $abort = false;

    private function abort($message) {
        $errorMessage = ErrorHandler::handleError(ErrorType::RequestCheck, $message);
        $this->abort = true;
        echo $errorMessage;
    }

    private function validateHTTPRequest() {
        $message = "";
        if(!isset($this->request)) {
            $message = "Request not initialized, placeholder";
        }
        if(!isset($this->request["HTTP_USER_AGENT"])) {
            $message = "Invalid user agent, placeholder";
        }
        if($this->request["SERVER_PROTOCOL"] != "HTTP/1.1") {
            $message = "Insecure connection, placeholder";
        }
        if(!isset($this->request["REQUEST_METHOD"])) {
            $message = "No request method specified, placeholder";
        }

        if($message != "") {
            $this->abort($message);
        }
    }

    public function route($referer){
        $this->validateHTTPRequest();
        if(!$this->abort) {
            $method = $this->request["REQUEST_METHOD"];
            $content = file_get_contents("php://input");
            if($content) {
                $api = new API($method, $content);
                if($api->loadConfig("../config/config.json")) {
                    $api->handleRequest($referer);
                }
            } else {
                $this->abort("Unable to read request body.");
            }
        }
    }

    public function __construct($request) {
        $this->request = $request;
    }
};
?>