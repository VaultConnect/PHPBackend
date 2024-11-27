<?php
    require_once("error.php");
    require_once("util.php");

    class API {
        private $_method;
        private $_requestContent;
        private $config;

        public function __construct($method, $requestContent) {
            $this->_method = $method;
            try {
                $this->_requestContent = json_decode($requestContent, true);
            } catch(Exception $exception) {
               $this->abort($exception->getMessage());
            }
        }
        public function loadConfig($filePath) {
            $this->config = new BackendConfig();
            if(!$this->config->loadConfig($filePath)) {
                   $this->abort("Unable to load config.");
            }
        }

        public function respond($data) {
            if(!empty($data)) {
                header("Content-Type: application/json");
                try {
                    if(json_validate($data)) {
                        echo $data;
                    } else if(is_array($data)) {
                        $data = json_encode($data, JSON_FORCE_OBJECT);
                        echo $data;
                    }
                } catch(Exception $exception) {
                    $this->abort($exception->getMessage());
                }
            }
        }

        public function abort($reason) {
            $errorData = ErrorHandler::handleError(ErrorType::APIException, $reason);
            $this->respond($errorData);
        }

        public function handleRequest($referer) {
            switch($referer) {
                case Referer::Login:
                    $responseData = array("data" => "Response");
                    $this->respond($responseData);
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