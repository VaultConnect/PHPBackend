<?php
    require_once("error.php");
    require_once("util.php");
    require_once("database.php");

    class API {
        private $method;
        private $requestContent;
        private $config;
        private $status;
        private $database;
        private $abort;

        public function __construct($method, $requestContent, $configPath) {
            header(header: "Content-Type: application/json");
            $this->status = new BackendStatus;

            if($this->loadConfig(filePath: $configPath)) {
                $this->method = $method;
                // placeholder
                $this->status->DB = 1;
                $this->status->Router = 1;
                try {
                    // $debug = '{"username:" "Test"}';
                    $this->requestContent = json_decode(json: $requestContent, associative: false);
                    // echo $this->$requestContent;
                    $this->database = new Database(config: $this->config);
                } catch(Exception $exception) {
                   $this->abort(reason: $exception->getMessage());
                }
            } else {
                $this->abort(reason: "Unable to initialize API.");
            }
        }

        private function loadConfig($filePath): bool {
            $this->config = new BackendConfig();
            if(!$this->config->loadConfig(filePath: $filePath)) {
                $this->abort(reason: "Unable to load config.");
                return false;
            }
            return true;
        }

        private function respond($data): void {
            if(!empty($data)) {
                try {
                    if(is_array(value: $data)) {
                        $data = json_encode(value: $data, flags: JSON_FORCE_OBJECT);
                        echo $data;
                    } else if(json_validate(json: $data)) {
                        echo $data;
                    }
                } catch(Exception $exception) {
                    $this->abort(reason: $exception->getMessage());
                }
            }
        }

        private function abort($reason): void {
            $this->status->Router = "Down"; // placeholder
            $errorData = ErrorHandler::handleError(errorType: ErrorType::APIException, message: $reason);
            $this->respond(data: $errorData);
        }
        
        /* GET -> Check if this user exists
           POST -> Login action */
        private function handleLogin(): array | null {
            if(empty($this->requestContent->{"username"}))
                return null;
            
            return match($this->method) {
                "GET" => array("status" => "200", "data" => ($this->database->userExists(username: $this->requestContent->{"username"}))),
                "POST" => (function (): array {
                    echo "<".$this->requestContent->{"username"}.",".hash(algo: "sha256", data: $this->requestContent->{"password"}, binary: false).">";
                    $sessionToken = $this->database->userLogin(username: $this->requestContent->{"username"},
                                                               password: hash(algo: "sha256", data: $this->requestContent->{"password"}, binary: false));
                    
                    if($sessionToken == "") {
                        return array("status" => "401", "data" => "Authentication failed.");
                    } else {
                        return array("status" => "200", "data" => $sessionToken);
                    }
                })(),
                default => null,
            };
        }

        private function handleRegister(): array | null {
            if(empty($this->requestContent->{"username"}) || empty($this->requestContent->{"password"})
            || empty($this->requestContent->{"email"}))
                return null;
            
            return match($this->method) {
                "POST" => array("status" => "201",
                                "data" => $this->database->createUser(username: $this->requestContent->{"username"},
                                                                      password: hash(algo: "sha256",
                                                                                     data: $this->requestContent->{"password"},
                                                                                     binary: false),
                                                                      email: $this->requestContent->{"email"},
                                                                      roles: "employee")),
                default => null,
            };
        }
        
        /* GET -> Check whether privileges suffice 
           POST -> GET + action */
        private function deleteUser(): array | null {
            if(empty($this->requestContent->{"username"}) || empty($this->requestContent->{"SessionToken"})
            || empty($this->requestContent->{"targetUsername"}))
                return null;
            $user = $this->requestContent->{"username"};
            $targetUser = $this->requestContent->{"targetUsername"};
            $userExists = $this->database->userExists(username: $targetUser);

            if($userExists) {
                $userFlags = $this->database->userFlags(username: $user);
                $allowDeletion = (($userFlags->admin || $userFlags->userManagement) || ($user == $targetUser));
                $response = match($this->method) { 
                    "GET" => array("status" => "200", "data" => $allowDeletion),
                    "POST" => ($allowDeletion) ? array("status" => "201", "data" => $this->database->deleteUser(username: $targetUser))
                                               : array("status" => "403"),
                    default => null,
                };

                return $response;
            } else {
                return array("status" => "404", "data" => "User does not exist.");
            }
        }

        private function provideContent(): string {
            return "placeholder";
        }

        public function handleRequest($referer): void { 
            if($this->status->fullWorking()) {
                $responseData = null;
                switch($referer) {
                    case Referer::Login:
                        $responseData = $this->handleLogin();
                        break;
                    case Referer::Register:
                        $responseData = $this->handleRegister();
                        break;
                    case Referer::Delete:
                        $responseData = $this->deleteUser();
                        break;
                    case Referer::AggregateContent:
                        $responseData = $this->provideContent();
                        break;
                    case Referer::Status:
                        $responseData = match($this->method) {
                            "GET" => BackendStatus::serialize(status: $this->status),
                            default => null,
                        };
                        break;
                }
                if(empty($responseData) || $responseData == null) {
                    $this->abort(reason: "Request could not be handled.");
                } else {
                    $this->respond(data: $responseData);   
                }
            } else {
                $this->abort(reason: "Unable to handle request, system down");
            }
        }
    };
?>
