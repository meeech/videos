<?php
require 'php/header.php';

setcookie('videos5_user', '', time() - 3600);
$login_errors = 'You have been logged out. Be excellent to each other.';
require('views/login.php');

