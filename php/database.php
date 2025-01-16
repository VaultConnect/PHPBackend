<?php
    require_once("util.php");

    class Database {
        private $pdo;
        private $host;
        private $user;
        private $name;
        private $key;

        private function loadConfig($config): void {
            $this->host = $config->DB_Host;
            $this->user = $config->DB_User;
            $this->name = $config->DB_Name;
            $this->key = $config->DB_Key;            
        }

        public function __construct($config) {
            $this->loadConfig(config: $config);
            try {
                $this->pdo = new PDO(dsn: "mysql:host=$this->host;dbname=$this->name",
                                     username: $this->user, password: $this->key);
                $this->pdo->setAttribute(attribute: PDO::ATTR_ERRMODE, value: PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(attribute: PDO::ATTR_EMULATE_PREPARES, value: false);
                // $this->execStatement(statement: "USE VaultConnectDB;");
            } catch(PDOException $e) {
                echo $e->getMessage();
                // todo: error handling
            }
        }

        private function execStatement($statement, $data = null, $all = false, $mode = PDO::FETCH_ASSOC): array | bool | null {
            $PDOStatement = $this->pdo->prepare(query: $statement);
            if(!empty($data)) {
                foreach($data as $entry) {
                    $PDOStatement->bindValue(param: $entry["key"], value: $entry["value"], type: PDO::PARAM_STR);
                }
            }
            // echo print_r($PDOStatement->debugDumpParams());
            // echo print_r($statement);
            try {
                $result = $PDOStatement->execute();
                if($result) {
                    return ($all) ? $PDOStatement->fetchAll($mode) : $PDOStatement->fetch(mode: $mode);
                } else {
                    return null;
                }
            } catch(PDOException $e) {
                echo $e;
                return null;
            }
        }

        private function newKV($key, $value): array{
            return array(
                "key" => $key,
                "value" => $value
            );
        }

        public function userExists($username): bool {
            $data = array(
                0 => $this->newKV(key: "username", value: $username)
            );
            $result = $this->execStatement(statement: "SELECT id FROM users WHERE users.username = :username", data: $data);

            return ($result != null && !empty($result));
        }

        private function generateSessionToken(): string {
            $rand = random_bytes(length: 16);
            $rand[6] = chr(codepoint: ord(character: $rand[6]) & 0x0f | 0x40);
            $rand[8] = chr(codepoint: ord(character: $rand[8]) & 0x3f | 0x80);
            
            $uuid = vsprintf(format: "%s%s-%s-%s-%s-%s%s%s", values: str_split(string: bin2hex(string: $rand), length: 4));
            return hash(algo: "sha256", data: $uuid, binary: false);
        }

        public function userLogin($username, $password): string {
            $data = array(
                0 => $this->newKV(key: ":username", value: $username),
                1 => $this->newKV(key: ":password", value: $password),
            );
            $result = $this->execStatement(statement: "SELECT id FROM users WHERE users.username = :username AND users.password = :password", data: $data);
            
            if($result == null) {
                return "";
            } else {
                $sessionToken = $this->generateSessionToken();
                $data = array(
                    0 => $this->newKV(key: "token", value: $sessionToken),
                    1 => $this->newKV(key: "username", value: $username)
                );
                // echo $sessionToken;
                $this->execStatement(statement: "UPDATE auth SET auth.token = :token
                                                 WHERE EXISTS (
                                                    SELECT 1 FROM users 
                                                    WHERE auth.user_id = users.id AND users.username = :username
                                                );", data: $data);
                return $sessionToken;
            }
        }

        public function changePassword($user, $newPassword): string {
            $data = array(
                0 => $this->newKV(key:"username", value:$user),
                1 => $this->newKV(key:"password", value:$newPassword),
            );
            $result = $this->execStatement(statement: "UPDATE users SET users.password = :password
                                                       WHERE users.username = :username;", data: $data);
            return $user." ".$newPassword;
        }

        public function changeUsername($origUsername, $newUsername): bool {
            $data = array(
                0 => $this->newKV(key:"username", value:$newUsername),
                1 => $this->newKV(key:"originalusername", value:$origUsername),
            );
            $result = $this->execStatement(statement: "UPDATE users SET users.username = :username WHERE users.username = :originalusername;", data: $data);
            return $result != null;
        }

        public function changeEmail($user, $email): bool {
            $data = array(
                0 => $this->newKV(key:"username", value:$user),
                1 => $this->newKV(key:"email", value:$email),
            );
            $result = $this->execStatement(statement: "UPDATE users SET users.email = :email WHERE users.username = :username;", data: $data);
            return $result != null;
        }

        public function userFlags($username): UserFlags | string {
            $data = array(
                0 => $this->newKV(key:"username", value: $username),
            );
            $result = $this->execStatement(statement:"SELECT flags FROM users WHERE users.username = :username", data: $data);
            return UserFlags::deserialize($result["flags"]);
            /* return match($result["flags"]) {
                "employee" => new InternalUser,
                "customer" => new ForeignUser,
                "admin" => new AdminUser,
                default => null
            }; */
        }

        public function setUserFlags($username, $flags): bool {
            $data = array(
                0 => $this->newKV(key:"username", value: $username),
                1 => $this->newKV(key:"flags", value: $flags->serialize()),
            );
            $result = $this->execStatement(statement: "UPDATE users SET users.flags = :flags WHERE users.username = :username", data: $data);
            return true;
        }

        public function allUsers($filter = null): array {
            $result = $this->execStatement(statement: "SELECT * FROM users;", all: true);
            $users = [];
            foreach($result as $user) {
                $users[] = new User(flags: UserFlags::deserialize(data: $user["flags"]), 
                                    id: $user["id"],
                                    email: $user["email"],
                                    username: $user["username"]);
            }
            return $users;
        }

        public function verifyUser($username, $token = null, $password = null): bool {
            $data = array(
                0 => $this->newKV(key:"username", value: $username),
                1 => $this->newKV(key:"token", value: $token)
            );

            if($this->userExists(username: $username)) {
                $result = false;
                if($token != null) {
                    $result = $this->execStatement(statement:"SELECT auth_id FROM auth
                                                              JOIN users ON users.id = auth.user_id
                                                              WHERE users.username = :username AND auth.token = :token;", data: $data);
                    $dump = file_put_contents(filename: "log.txt", data: "$username, $token");
                    if($result) {
                        return true;
                    }
                } else if($password != null) {
                    $result = $this->execStatement(statement: "SELECT id FROM users WHERE users.username = :username AND users.password = :password", data: $data);
                    if(!empty($result["id"])) {
                        return true;
                    }
                } else {
                    return false;
                }
                return ($result && !empty($result));
            } else {
                return false;
            }
        }
        
        public function createUser($username, $password, $email, $roles): bool {
            $userData = array(
                0 => $this->newKV(key:":username", value: $username),
                1 => $this->newKV(key:":password", value: $password),
                2 => $this->newKV(key:":email", value: $email),
                3 => $this->newKV(key:":flags", value: $roles)
            );
            $userId = array( 0 => $this->newKV(key:"username", value: $username) );
            $authToken = array();

            $userExists = $this->userExists(username: $username);
            if($userExists){
                return false;
            } else {
                $this->execStatement(statement: "INSERT INTO users (username, password, email, flags) VALUES(:username, :password, :email, :flags)", data: $userData);
                $result = $this->execStatement(statement: "SELECT id FROM users WHERE users.username = :username;", data: $userId);
                $authToken = array( 0 => $this->newKV(key:"userid", value: $result["id"]) );
                $this->execStatement(statement: "INSERT INTO auth (user_id) VALUES(:userid);", data: $authToken);
                return true;
            }
        }
    
        public function deleteUser($username): bool {
            $data = array(
                0 => $this->newKV(key:"username", value: $username)
            );
            $result = $this->execStatement(statement:"DELETE FROM users WHERE users.username = :username", data: $data);
            // todo: Check for success and implement further security
            return $result != null;
        }

        private function userLocked($username): bool {
            $data = array(
                0 => $this->newKV(key:"username", value: $username)
            );
            $result = $this->execStatement(statement:"SELECT id FROM users WHERE users.username = :username AND users.locked_at", data: $data);
            return ($result && !empty($result));
        }
    }
?>