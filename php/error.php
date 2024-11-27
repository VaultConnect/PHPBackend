<?php
    enum ErrorType {
        case RequestCheck;
        case InvalidMethod;
        case APIException;
        case Unknown;
    };
    class ErrorHandler {
        private static function enumToString($errorType) {
            switch($errorType) {
                case ErrorType::RequestCheck:
                    return "RequestCheck";
                case ErrorType::InvalidMethod:
                    return "InvalidMethod";
                case ErrorType::APIException:
                    return "APIException";
                case ErrorType::Unknown:
                default:
                    return "Unknown";
            }
        }

        private static function formatErrorMessage($errorType, $message) {
            $data = array("Error" => ErrorHandler::enumToString($errorType),
                          "Message" => $message);
            return json_encode($data);
        }

        public static function handleError($errorType, $message) {
            if(empty($errorType) || empty($message)) {
                return "";
            } else {
                return ErrorHandler::formatErrorMessage($errorType, $message);
            }
        }
    };
?>