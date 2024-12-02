<?php 
    class BackendConfig {
        public $DB_Name = null;
        public $DB_Host = null;
        public $DB_User = null;
        public $DB_Key = null;

        public function __construct($filePath = false) {
            if(file_exists(filename: $filePath)) {
                $this->loadConfig(filePath: $filePath);
            }
        }

        private function validateConfig(): bool {
            $checksPassed = true;
            if(empty($this->DB_Name))
                $checksPassed = false;
            else
                $this->DB_Name = trim(string: $this->DB_Name);
            
            if(empty($this->DB_Host))
                $checksPassed = false;
            else
                $this->DB_Host = trim(string: $this->DB_Host);
            
            if(empty($this->DB_User))
                $checksPassed = false;
            else
                $this->DB_User = trim(string: $this->DB_User);
            
            if(empty($this->DB_Key))
                $checksPassed = false;
            else
                $this->DB_Key = trim(string: $this->DB_Key);

            return $checksPassed;
        }

        public function loadConfig($filePath): bool {
            if(empty($filePath)
            || !file_exists(filename: $filePath))
                return false;
            
            $handle = fopen(filename: $filePath, mode: 'r') or die("File not found.");
            $fileContent = fread(stream: $handle, length: filesize(filename: $filePath));
            fclose(stream: $handle);

            try {
                $jsonObject = json_decode(json: $fileContent);
                $this->DB_Name = $jsonObject->{"DB_Name"};
                $this->DB_Host = $jsonObject->{"DB_Host"};
                $this->DB_User = $jsonObject->{"DB_User"};
                $this->DB_Key = $jsonObject->{"DB_Key"};
                return $this->validateConfig();
            } catch(Exception $exception) {
                echo "Error while reading config file: ".$exception->getMessage();
                return false;
            }
        }
    };

    class BackendStatus {
        public $DB = 0;
        public $Router = 0;

        public function fullWorking(): bool {
            return $this->DB && $this->Router;
        }

        public static function serialize($status): string {
            $data = array("DB" => ($status->DB) ? "Healthy" : "Down", 
                          "Router" => $status->Router) ? "Healthy" : "Down";
            return json_encode(value: $data);
        }
    };

    class UserFlags {
        public $admin;          // Other permissions are irrelevant when this flag is set
        public $userManagement; // Can create, delete and manage users
        public $dataManagement; // Can manipulate data based on the companyAccess flag
        public $companyAccess;  // If set the user can view and manage corporate data

        public function serialize(): string {
            return "admin:".$this->admin.",userManagement:".$this->userManagement.
                   ",dataManagement:".$this->dataManagement.",companyAccess:".$this->companyAccess;
        }

        public static function deserialize($data): UserFlags {
            $entries = explode(separator: ',', string: $data);

            $userFlags = new UserFlags();
            foreach($entries as $entry) {
                $splitPos = strpos($entry, ':', 0);
                $key = substr($entry, 0, $splitPos);
                $value = substr($entry, $splitPos+1, strlen($entry)-$splitPos) == "true";
                switch($key) {
                    case "admin":
                        $userFlags->admin = $value;
                        break;
                    case "userManagement":
                        $userFlags->userManagement = $value;
                        break;
                    case "dataManagement":
                        $userFlags->dataManagement = $value;
                        break;
                    case "companyAccess":
                        $userFlags->companyAccess = $value;
                    default:
                        break;
                }
            }

            return $userFlags;
        }
    }

    class ForeignUser extends UserFlags {
        public function __construct() {
            $this->admin = false;
            $this->userManagement = false;
            $this->dataManagement = true;
            $this->companyAccess = false;
        }
    };

    class InternalUser extends UserFlags {
        // todo: Allow company employees to manage foreign users
        public function __construct() {
            $this->admin = false;
            $this->userManagement = false;
            $this->dataManagement = true;
            $this->companyAccess = true;
        }
    };

    class AdminUser extends UserFlags {
        public function __construct() {
            $this->admin = true;
            $this->userManagement = true;
            $this->dataManagement = true;
            $this->companyAccess = true;
        }
    }
?>