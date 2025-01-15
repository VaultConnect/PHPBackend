<?php
    require_once("../api/router.php");
    $router = new Router(request: $_SERVER);
    $router->route(referer: Referer::Update);
?>