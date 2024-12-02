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
            /* $this->loadConfig(config: $config);
            $this->pdo = new PDO(dsn: "mysql:host=$this->host;dbname=$this->name",
                       username: $this->user, password: $this->key);
            $this->pdo->setAttribute(attribute: PDO::ATTR_ERRMODE, value: PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(attribute: PDO::ATTR_EMULATE_PREPARES, value: false); */
        }

        private function execStatement($statement, $data): array | null {
            $PDOStatement = $this->pdo->prepare(query: $statement);
            if($PDOStatement && is_array(value: $data)) {
                $result = $PDOStatement->execute(params: $data);
                return ($result) ? $PDOStatement->fetchAll(PDO::FETCH_ASSOC) : null;
            }
            return null;
        }

        public function userExists($username): null {
            return null;
        }
        public function userLogin($username, $password): string {
            return ($username = " " && $password == " ") ? "1" : "";
        }
        public function userFlags($username): UserFlags {
            return new AdminUser();
        }
        public function verifyUser($username, $token): bool {
            return true;
        }
        public function userRoles($username): null {
            return null;
        }
        public function createUser($username, $password, $email, $roles): null {
            return null;
        }
        public function deleteUser($username): null {
            return null;
        }
        public function userLocked($username): null {
            return null;
        }
    }
?>