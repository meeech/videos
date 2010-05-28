<?php require 'views/header.php' ?>

<?php
$page = 'home';

if(!isset($_COOKIE['videos5_user'])) {
    $page = 'login';
}

if(file_exists("views/{$page}.php")) { require("views/{$page}.php"); };
?>

<?php require 'views/footer.php' ?>


