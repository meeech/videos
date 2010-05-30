<?php
require_once('php/config.php');
require_once('php/encoder.php');
require_once('php/db.php');
require_once('php/helper.php');

$page = 'home';

if(!isset($_COOKIE['videos5_user'])) {
    $page = 'login';
}
else if(isset($_GET['page'])) {
    $page = $_GET['page'];
    //Sanitize page
    $page = pathinfo($page,PATHINFO_BASENAME);
}