<?php
    require_once("../php/router.php");
    $router = new Router(request: $_SERVER);
    $router->route(referer: Referer::Register);
?>