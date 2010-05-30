<?php
//Delete cookie / cookie info first
setcookie('videos5_user', '', time() - 3600);
unset($_COOKIE['videos5_user']);

//then throw in header.php, which can clearly guess the page. 
require 'php/header.php';

$login_errors = 'You have been logged out. Be excellent to each other.';
require('views/login.php');

