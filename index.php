<?php 
require_once 'php/header.php';
if(!isset($_GET['page'])) {
    require 'views/header.php';
}

?>

<?php
$page = 'home';

if(!isset($_COOKIE['videos5_user'])) {
    $page = 'login';
} 
else if(isset($_GET['page'])) {
    $page = $_GET['page'];
}

if(file_exists("views/{$page}.php")) { require("views/{$page}.php"); };
?>

<?php 
if(!isset($_GET['page'])) {
    require 'views/footer.php';
    }
?>


