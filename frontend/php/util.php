<?php
    class WebUtil {
        private static function baseRequest(IRoute $url, $content, $method): null | array {
            print_r($content);
            echo "\n";
            $context = stream_context_create([
                'http' => [ 
                'method' => $method,
                'header' => ['Content-Type: application/json', 'User-Agent: testuser'],
                'content' => json_encode($content), ]
            ]);
            
            $response = file_get_contents($url->toString(), false, $context);
            if(json_validate($response)) {
                return json_decode($response, true);
            } else {
                return null;
            }
        }
        static function getRequest($url, $content): null | array {
            return WebUtil::baseRequest($url, $content, "GET");
        }

        static function postRequest($url, $content): null | array {
            return WebUtil::baseRequest($url, $content, "POST");
        }
    }

    interface IRoute {
        public function toString(): string;
    }
    
    enum Route implements IRoute {
        case Login;
        case Register;
        case Content;
        case Update;
        
        public function toString(): string {
            return match($this) {
                Route::Login => "http://localhost/PHPPPBackend/api/login.php",
                Route::Register => "http://localhost/PHPPPBackend/api/register.php",
                Route::Content => "http://localhost/PHPPPBackend/api/content.php",
                Route::Update => "http://localhost/PHPPPBackend/api/update.php",
            };
        }
    };

    function changePassword($username, $token, $oldPassword, $newPassword) {
        $request = ["username" => $username,
                    "SessionToken" => $token,
                    "type" => "newPassword",
                    "target" => $username,
                    "oldPassword" => $oldPassword,
                    "newPassword" => $newPassword];
        $response = WebUtil::postRequest(url: Route::Update, content: $request);
    }

    function serverRenderUsers($username, $token, $content) {
        $request = ["username" => $username,
                    "SessionToken" => $token,
                    "content" => $content];
        $response = WebUtil::postRequest(Route::Content, $request);
        print_r($response);
        return $response["data"];
    }
?>
