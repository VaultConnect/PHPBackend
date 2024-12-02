<?php
    enum ErrorType {
        case RequestCheck;
        case InvalidMethod;
        case APIException;
        case Unknown;
    };
    class ErrorHandler {
        private static function enumToString($errorType): string {
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

        private static function formatErrorMessage($errorType, $message): bool|string {
            $data = array("Error" => ErrorHandler::enumToString(errorType: $errorType),
                          "Message" => $message,
                          "Time" => (new DateTime(datetime: "now"))->format(format: "D-M-Y h:i:s"));
            return json_encode(value: $data);
        }

        public static function handleError($errorType, $message): bool|string {
            if(empty($errorType) || empty($message)) {
                return "";
            } else {
                return ErrorHandler::formatErrorMessage(errorType: $errorType, message: $message);
            }
        }
    };
?>