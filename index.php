<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require "./vendor/autoload.php";
$frontController = new Idealo\Controllers\FrontController();

