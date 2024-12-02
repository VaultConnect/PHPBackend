<?php 
require_once("error.php");
require_once("api.php");
enum Referer {
    case Login;
    case Register;
    case Delete;
    case Status;
    case AggregateContent;
};

class Router {
    private $request;
    private $abort = false;

    private function abort($message): void {
        $errorMessage = ErrorHandler::handleError(errorType: ErrorType::RequestCheck, message: $message);
        $this->abort = true;
        echo $errorMessage;
    }

    private function validateHTTPRequest(): void {
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
            $this->abort(message: $message);
        }
    }

    public function route($referer): void{
        $this->validateHTTPRequest();
        if(!$this->abort) {
            $method = $this->request["REQUEST_METHOD"];
            $content = file_get_contents(filename: "php://input");
            if($content) {
                $api = new API(method: $method, requestContent: $content, configPath: "../config/config.json");
                $api->handleRequest(referer: $referer);
            } else {
                $this->abort(message: "Unable to read request body.");
            }
        }
    }

    public function __construct($request) {
        $this->request = $request;
    }
};
?>