<?php
    require_once("../php/router.php");
    $router = new Router($_SERVER);
    $router->route(Referer::Login);
?>