<?php
    function safeReadJSON($filePath) {

    }

    class BackendConfig {
        public $DB_Name = null;
        public $DB_Host = null;
        public $DB_User = null;
        public $DB_Key = null;
        public $PAGE_Permissions = null;

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

            if(empty($this->PAGE_Permissions))
                $checksPassed = false;

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
                $this->PAGE_Permissions = $jsonObject->{"PAGE_Permissions"};
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

    class User {
        public $id;
        public $email;
        public $username;
        public $userFlags;

        public function __construct(UserFlags $flags, $id, $email, $username) {
            $this->id = $id;
            $this->email = $email;
            $this->username = $username;
            $this->userFlags = $flags;
        }
    }

    class ContentProvider {
        private $requirements;
        private $files;

        // todo: Error handling
        private function readDirectory($path) {
            $it = new DirectoryIterator(directory: $path);
            while($it->valid() && !$it->isDot()) {
                $fileName = $it->getFilename();
                $splitPos = strrpos(haystack: $fileName, needle: ".");
                $fileDescriptor = substr(string: $fileName,
                                         offset: 0,
                                         length: $splitPos + 1);
                $fileType = substr(string: $fileName,
                                         offset: $splitPos+1,
                                         length: strlen(string: $fileName)-$splitPos);
                if($fileDescriptor == "perm" && $fileType == "json") {
                    
                } else {
                    $this->files[] = array("name" => $fileDescriptor,
                                           "type" => $fileType);
                }
                $it->next();
            }
        }

        public function __construct($contentPath) {
            $this->readDirectory(path: $contentPath);
        }
    }

    

?>
