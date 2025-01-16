<?php
    class WebUtil {
        private static function baseRequest(IRoute $url, $content, $method): null | array {
            echo "content ".$url->toString();
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
        case Delete;
        case Content;
        case Update;
        
        public function toString(): string {
            return match($this) {
                Route::Login => "http://localhost/PHPPPBackend/api/login.php",
                Route::Register => "http://localhost/PHPPPBackend/api/register.php",
                Route::Delete => "http://localhost/PHPPPBackend/api/delete.php",
                Route::Content => "http://localhost/PHPPPBackend/api/content.php",
                Route::Update => "http://localhost/PHPPPBackend/api/update.php",
            };
        }
    };

    function changePassword($username, $token, $newPassword) {
        $request = ["username" => $username,
                    "SessionToken" => $token,
                    "type" => "passwordChange",
                    "target" => $username,
                    "newPassword" => $newPassword];
        $response = WebUtil::postRequest(url: Route::Update, content: $request);
        echo "Response: ".print_r($response);
    }

    function serverRenderUsers($username, $token, $content) {
        $request = ["username" => $username,
                    "SessionToken" => $token,
                    "content" => $content];
        $response = WebUtil::postRequest(Route::Content, $request);
        return $response["data"];
    }

    interface IWebPage {
        public function toString(): string;
        public static function nameToPage($pagename): IWebPage | null;
        public static function allowAccess($username, $authToken, $page): bool;
    }

    enum WebPage implements IWebPage {
        case home;
        case dashboard;
        case login;
        case logout;
        case register;
        case management;
        case passwordChange;

        public function toString(): string {
            return match($this) {
                WebPage::home => "home",
                WebPage::dashboard => "dashboard",
                WebPage::login => "login",
                WebPage::logout => "logout",
                WebPage::register => "register",
                WebPage::management => "management",
                WebPage::passwordChange => "passwordChange",
            };
        }

        public static function nameToPage($pagename): WebPage | null {
            foreach(WebPage::cases() as $page) {
                if($page->toString() == $pagename) {
                    return $page;
                }
            }
            return null;
        }

        public static function allowAccess($username, $authToken, $page): bool {
            return match($page) {
                WebPage::home => true,
                WebPage::dashboard => ($username != null && $authToken != null),
                WebPage::login => true,
                WebPage::logout => true,
                WebPage::register => true,
                WebPage::management => ($username != null && $authToken != null),
                WebPage::passwordChange => ($username != null && $authToken != null),
            };
        }
    }

    function loadPage($page = null) {
        $site = isset($_GET["site"]);
        $username = (isset($_COOKIE["username"])) ? $_COOKIE["username"] : null;
        $authToken = (isset($_COOKIE["authToken"])) ? $_COOKIE["authToken"] : null;

        if($page != null && ($page = WebPage::nameToPage($page))) {
            if($page->toString() == "logout") {
                // $_SERVER["REQUEST_METHOD"] = "GET";
                setcookie("username", "");
                setcookie("authToken", "");
                header("Location: index?page=login");
                // require_once("pages/login.php");
            } else if(WebPage::allowAccess($username, $authToken, $page)) {
                require_once("pages/".$page->toString().".php");
            } else {
                require_once("pages/access.php");
            }
        } else if($site && ($site = WebPage::nameToPage($site))) {
            if($site->toString() == "logout") {
                setcookie("username", "");
                setcookie("authToken", "");
                header("Location: index?page=login");
            } else if(WebPage::allowAccess($username, $authToken, $site)) {
                require_once("pages/".$site->toString().".php");
            } else {
                require_once("pages/access.php");
            }
        }
    }
?>
