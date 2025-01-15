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
                    } else if(json_decode(json: $data) != null) {
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

        private function handleUpdate(): array | null {
            if(empty($this->requestContent->{"username"}) || empty($this->requestContent->{"SessionToken"})
            || empty($this->requestContent->{"type"})) {
                return null;
            }
            $username = $this->requestContent->{"username"};
            $token = $this->requestContent->{"SessionToken"};
            $type = $this->requestContent->{"type"};
            $validated = $this->database->verifyUser(username: $username, token: $token);
            $execUser = $this->database->userFlags(username: $username);
            if(!$execUser->userManagement || empty($this->requestContent->{"target"})) {
                return array("status" => "400");
            }

            $target = $this->requestContent->{"target"};

            if($validated) {
                switch($type) {
                    case "delete":
                        $this->database->deleteUser(username: $target);
                        break;
                    case "email":
                        if(empty($this->requestContent->{"mail"})) {
                            return array("status" => "400");
                        }
                        $this->database->changeEmail(user: $target, email: $this->requestContent->{"mail"});
                        break;
                    case "username":
                        if(empty($this->requestContent->{"newUsername"})) {
                            return array("status" => "400");
                        }
                        $this->database->changeUsername(origUsername: $target, newUsername: $this->requestContent->{"newUsername"});
                        break;
                    case "passwordChange":
                        if(empty($this->requestContent->{"oldPassword"})
                        || empty($this->requestContent->{"newPassword"})) {
                            return array("status" => "400");
                        }
                        if(!$this->database->verifyUser(username: $username, password: $this->requestContent->{"oldPassword"})) {
                            return array("stauts" => "401");
                        }
                        $this->database->changePassword(user: $target, newPassword: $this->requestContent->{"newPassword"});
                        break;
                    default:
                        break;
                }
                return array("status" => "200");
            } else {
                return null;
            }
        }

        private function provideContent(): array | null {
            if(empty($this->requestContent->{"username"}) || empty($this->requestContent->{"SessionToken"})
            || empty($this->requestContent->{"content"})) {
                return null;
            }
            $username = $this->requestContent->{"username"};
            $token = $this->requestContent->{"SessionToken"};
            $content = $this->requestContent->{"content"};
            $validated = $this->database->verifyUser(username: $username, token: $token);

            if($validated) {
                $returnData = "";
                switch($content) {
                    case "users":
                        $users = $this->database->allUsers();
                        foreach($users as $user) {
                            $type = ($user->userFlags->admin) ? "Admin" : "User";
                            $returnData += "<tr>\n";
                            $returnData += "<th scope='row'>$user->id</th>\n";
                            $returndata += "<td>$user->username</td>\n";
                            $returndata += "<td>$user->email</td>\n";
                            $returndata += "<td>$type</td>\n";
                            $returnData += "</tr>\n";
                        }
                        break;
                    default:
                        $returnData = "";
                        break;
                }
                return array("status" => "200", "data" => json_encode(value: $returnData));
            } else {
                return null;
            }
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
                    case Referer::Update:
                        $responseData = $this->handleUpdate();
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
