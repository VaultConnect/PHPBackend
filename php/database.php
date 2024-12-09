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
                    $dbResult = $PDOStatement->fetch(mode: $mode);
                    return ($all) ? $PDOStatement->fetchAll($mode) : $dbResult;
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
                    0 => $this->newKV(key: "token", value: hash(algo: "sha256", data: $sessionToken, binary: false)),
                    1 => $this->newKV(key: "username", value: $username)
                );
                // echo $sessionToken;
                // $this->execStatement(statement: "UPDATE users SET users.active_auth = :token WHERE users.username = :username", data: $data);
                return $sessionToken;
            }
        }

        public function userFlags($username): UserFlags {
            $data = array(
                0 => $this->newKV(key:"username", value: $username),
            );
            $result = $this->execStatement(statement:"SELECT roles FROM users WHERE users.username = :username", data: $data);
            return match($result["roles"]) {
                "employee" => new InternalUser,
                "customer" => new ForeignUser,
                "admin" => new AdminUser,
                default => null
            };
        }

        public function verifyUser($username, $token): bool {
            $data = array(
                0 => $this->newKV(key:"username", value: $username),
                1 => $this->newKV(key:"token", value: $token)
            );
            $result = $this->execStatement(statement:"SELECT id FROM users WHERE users.username = :username AND users.", data: $data);
            return ($result && !empty($result));
        }
        
        public function createUser($username, $password, $email, $roles): bool {
            $data = array(
                0 => $this->newKV(key:":username", value: $username),
                1 => $this->newKV(key:":password", value: $password),
                2 => $this->newKV(key:":email", value: $email),
                3 => $this->newKV(key:":roles", value: $roles)
            );
            
            $result = $this->execStatement(statement: "INSERT INTO users (username, password, email, roles) VALUES(:username, :password, :email, :roles)", data: $data);
            // todo: Check for success
            return $result != null;
        }

        public function deleteUser($username): bool {
            $data = array(
                0 => $this->newKV(key:"username", value: $username)
            );
            $result = $this->execStatement(statement:"DELETE FROM users WHERE users.username = :username", data: $data);
            // todo: Check for success and implement further security
            return $result != null;
        }

        public function userLocked($username): bool {
            $data = array(
                0 => $this->newKV(key:"username", value: $username)
            );
            $result = $this->execStatement(statement:"SELECT id FROM users WHERE users.username = :username AND users.locked_at", data: $data);
            return ($result && !empty($result));
        }
    }
?>