<?php
    require_once("error.php");
    require_once("util.php");
    require_once("auth.php");

    class API {
        private $method;
        private $requestContent;
        private $config;

        public function __construct($method, $requestContent) {
            header("Content-Type: application/json");
            $this->method = $method;
            try {
                $this->requestContent = json_decode($requestContent, true);
            } catch(Exception $exception) {
               $this->abort($exception->getMessage());
            }
        }

        public function loadConfig($filePath) {
            $this->config = new BackendConfig();
            if(!$this->config->loadConfig($filePath)) {
                $this->abort("Unable to load config.");
                return false;
            }
            return true;
        }

        private function respond($data) {
            if(!empty($data)) {
                try {
                    if(is_array($data)) {
                        $data = json_encode($data, JSON_FORCE_OBJECT);
                        echo $data;
                    } else if(json_validate($data)) {
                        echo $data;
                    }
                } catch(Exception $exception) {
                    $this->abort($exception->getMessage());
                }
            }
        }

        private function abort($reason) {
            $errorData = ErrorHandler::handleError(ErrorType::APIException, $reason);
            $this->respond($errorData);
        }

        public function handleRequest($referer) {
            switch($referer) {
                case Referer::Login:
                    $responseData = match($this->method) {
                        "GET" => array("data" => $this->requestContent),
                        "POST" => array("data" => $this->requestContent),
                        default => null,
                    };
                    if(empty($responseData)) {
                        $this->abort("Login request was performed with invalid method");
                    } else {
                        $this->respond($responseData);   
                    }
                    break;
                case Referer::Register:
                    break;
                case Referer::Delete:
                    break;
                case Referer::AggregateContent:
                    break;
            }
        }
    };
?>