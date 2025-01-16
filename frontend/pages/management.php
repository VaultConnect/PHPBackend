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

    function showUsersTable() {
?>
        <div class="container">
            <form method="post">
            <div class="row">
                <div class="btn-group" role="group">
                    <button class="btn btn-primary" style="background-color: light-blue" type="submit" name="buttons" value="0">Delete</button>
                    <button class="btn btn-primary" style="background-color: light-blue" type="submit" name="buttons" value="1">Reset password</button>
                    <button class="btn btn-primary" style="background-color: light-blue" type="submit" name="buttons" value="2">Demote</button>
                    <button class="btn btn-primary" style="background-color: light-blue" type="submit" name="buttons" value="3">Promote</button>
                </div>
            </div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">E-Mail</th>
                        <th scope="col">Type</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    echo serverRenderUsers($_COOKIE["username"], $_COOKIE["authToken"], "users");
                ?>
                </tbody>
            </table>
            </form>
        </div>
<?php
    }

    function deleteUser($user, $sessionToken, $target) {
        $request = [
            "username" => $user,
            "SessionToken" => $sessionToken,
            "target" => $target,
        ];
        $response = WebUtil::postRequest(Route::Delete, $request);
        print_r($response);
    }

    function demoteUser($user, $sessionToken, $target) {
        $request = [
            "username" => $user,
            "SessionToken" => $sessionToken,
            "target" => $target,
            "type" => "demote",
        ];
        $response = WebUtil::postRequest(Route::Update, $request);
    }

    function promoteUser($user, $sessionToken, $target) {
        $request = [
            "username" => $user,
            "SessionToken" => $sessionToken,
            "target" => $target,
            "type" => "promote",
        ];
        $response = WebUtil::postRequest(Route::Update, $request);
    }

    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["buttons"])) {
        $username = $_COOKIE["username"];
        $sessionToken = $_COOKIE["authToken"];
        $target = $_POST["user"];

        switch($_POST["buttons"]) {
            case "0":
                deleteUser($username, $sessionToken, $target);
                break;
            case "1":
                // changePassword($username, $target, $sessionToken);
                break;
            case "2":
                demoteUser($username, $sessionToken, $target);
                break;
            case "3":
                promoteUser($username, $sessionToken, $target);
                break;
            default:
                break;
        }
        showUsersTable();
    } else {
        showUsersTable();
    }
?>
</body>
</html>