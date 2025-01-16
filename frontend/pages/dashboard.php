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
        function showActions() {
            ?>
            <form method="post">
            <div class="btn-group" role="group">
                <a class="btn btn-primary" name="action" href="?page=logout">Logout</a>
                <a type="submit" class="btn btn-primary" href="?page=passwordChange">Change Password</a>
                <button type="submit" class="btn btn-primary" name="action" value="2">---</button>
            </div>
            </form>
            <?php
        }
        showActions();
        if($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["from"])) {
            $_POST["from"] = "dash";
            $action = $_POST["action"];
            // print_r($_POST);
            switch($action) {
                case "0":
                    // loadPage("home");
                    // loadPage("logout");
                    $_SERVER["REQUEST_METHOD"] = "GET";
                    header("Location index?page=logout");
                    break;
                case "1":
                    $_SERVER["REQUEST_METHOD"] = "GET";
                    header("Location index?page=passwordChange");
                    break;
                case "2":
                    break;
            }
        }
    ?>
</body>
</html>