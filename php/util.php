<?php 
    class BackendConfig {
        private $DB_Name = null;
        private $DB_Host = null;
        private $DB_User = null;
        private $DB_Key = null;

        public function __construct($filePath = false) {
            if(file_exists($filePath)) {
                $this->loadConfig($filePath);
            }
        }

        private function validateConfig(): bool {
            $checksPassed = true;
            if(empty($this->DB_Name))
                $checksPassed = false;
            else
                $this->DB_Name = trim($this->DB_Name);
            
            if(empty($this->DB_Host))
                $checksPassed = false;
            else
                $this->DB_Host = trim($this->DB_Host);
            
            if(empty($this->DB_User))
                $checksPassed = false;
            else
                $this->DB_User = trim($this->DB_User);
            
            if(empty($this->DB_Key))
                $checksPassed = false;
            else
                $this->DB_Key = trim($this->DB_Key);

            return $checksPassed;
        }

        public function loadConfig($filePath): bool {
            if(empty($filePath)
            || !file_exists($filePath))
                return false;
            
            $handle = fopen($filePath, 'r') or die("File not found.");
            $fileContent = fread($handle, filesize($filePath));
            fclose($handle);

            try {
                $jsonObject = json_decode($fileContent);
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
?>