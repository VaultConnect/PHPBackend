<?php
    require_once("router.php");
    $router = new Router(request: $_SERVER);
    $router->route(referer: Referer::Delete);
?>