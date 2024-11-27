<?php 
    class BackendConfig {
        private $DB_Name = null;
        private $DB_Host = null;
        private $DB_User = null;
        private $DB_Key = null;

        public function __construct($filePath = false) {
            if(!empty($filePath)) {
                $this->loadConfig($filePath);
            }
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
            } catch(Exception $exception) {
                echo "Error while reading config file: ".$exception->getMessage();
                return false;
            }
            return true;
        }
    };
?>