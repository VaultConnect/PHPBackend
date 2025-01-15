<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    
</head>
<body>
<?php
    require_once("../php/util.php");

    function showLoginForm() {
?>
    <div class="container border rounded col-3">
        <form method="post">
            <legend>Login</legend>
            <div class="form-group">
                <div class="row">
                    <div class="col-12">
                        <label for="userName">Username:</label>
                        <input type="text" class="form-control" id="userName" name="userName" placeholder="Username" required>
                    </div> 
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <div class="col-12">
                        <label for="password">Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div> 
                </div>
            </div>  
            <div class="form-group">
                <div class="row">
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary bg-info">Login</button>    
                    </div> 
                </div>
            </div>
        </form>
    </div>
<?php
    }

    function userExists($username) {
        $request = ["username" => $username];
        $response = WebUtil::getRequest(Route::Login, $request);
        return $response["data"];
    }

    function login($username, $password) {
        $request = ["username" => $username,
                    "password" => hash(algo: "sha256",
                                       data: $password,
                                       binary: false)];
        $response = WebUtil::postRequest(Route::Login, $request);
        var_dump($response);
        return ($response["status"] == "200") ? $response["data"] : null;
    }
    
    function loginFailed($reason) {
        showLoginForm();
        echo "<h2>$reason</h2>";
    }

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST["userName"];
        $password = $_POST["password"];
        
        if(is_null($username)) {
            loginFailed("Empty username");
        } else if(is_null($password)) {
            loginFailed("Empty password");
        } else if(!userExists($username)) {
            loginFailed("No user with username '$username' found.");
        } else {
            $token = login($username, $password);
            if($token == null) {
                loginFailed("Incorrect password.");
            } else {
                setcookie("authToken", $token, time()+60*60*24*7);
                setcookie("username", $username, time()+60*60*24*7);
                $_SERVER["REQUEST_METHOD"] = "GET";
                header("Location: landing.php");
            }
        }
    } else {
        showLoginForm();
    }
?>

</body>
</html>
