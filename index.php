<?php 
require_once 'php/header.php';

if(!isset($_GET['page'])) {
    require 'views/header.php';
}

if(file_exists("views/{$page}.php")) { 
    require("views/{$page}.php"); 
};

if(!isset($_GET['page'])) {
    require 'views/foot.php';
    }
?>


