<?php require 'views/header.php' ?>

<?php
$page = 'home';
if(file_exists("views/{$page}.php")) { require("views/{$page}.php"); };
?>

<?php require 'views/footer.php' ?>


