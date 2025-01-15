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
    include_once("php/util.php");
    
    function showRegisterForm() {
?>
    <div class="container border rounded col-3">
        <form method="post">
            <legend>Register</legend>
            <div class="form-group">
                <div class="row">
                    <div class="col-12">
                        <label for="userEmail">E-Mail:</label>
                        <input type="email" class="form-control" id="userEmail" name="userEmail" placeholder="xyz@domain.com" required>
                    </div> 
                </div>
            </div>
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
                        <button type="submit" class="btn btn-primary bg-info">Register</button>    
                    </div> 
                </div>
            </div>
        </form>
    </div>
<?php
    }

    function register($email, $username, $password) {
        $request = ["username" => $username,
                    "password" => hash(algo: "sha256",
                                       data: $password,
                                       binary: false),
                    "email" => $email];
        $response = WebUtil::postRequest(Route::Register, $request);
    }

    function registerFailed($reason) {
        showRegisterForm();
        echo "<h2>$reason</h2>";
    }

    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST["userName"];
        $useremail = $_POST["userEmail"];
        $password = $_POST["password"];

        if(is_null($username)) {
            registerFailed("Empty username");
        } else if(is_null($password)) {
            registerFailed("Empty password");
        } else if(is_null($useremail)) {
            registerFailed("Empty Email");
        } else if(userExists($username)) {
            registerFailed("Username '$username' already exists.");
        } else {
            register($useremail, $username, $password);
            $_SERVER["REQUEST_METHOD"] = "GET";
            header("Location: frontend/pages/login.php");
        }
    } else {
        showRegisterForm();
    }
?>

</body>
</html>
