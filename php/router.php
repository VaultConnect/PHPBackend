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
        if(!isset($this->request)) {
            $this->abort("Request not initialized, placeholder");
        }
        if(!isset($this->request["HTTP_USER_AGENT"])) {
            $this->abort("Invalid user agent, placeholder");
        }
        if($this->request["SERVER_PROTOCOL"] != "HTTP/1.1") {
            $this->abort("Insecure connection, placeholder");
        }
        if(!isset($this->request["REQUEST_METHOD"])) {
            $this->abort("No request method specified, placeholder");
        }
    }

    public function route($referer){
        $this->validateHTTPRequest();
        if(!$this->abort) {
            $method = $this->request["REQUEST_METHOD"];
            $api = new API($method, "placeholder");
            $api->loadConfig("config/config.json"); // error handling, no hard coded filepath
            $api->handleRequest($referer);
        }
    }

    public function __construct($request) {
        $this->request = $request;
    }
};
?>