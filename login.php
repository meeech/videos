<?php
require 'php/header.php';
//Login Handler
//No high security here. move along.
$username = (isset($_POST['username'])) ? $_POST['username'] : false ;
$password = (isset($_POST['password'])) ? md5($_POST['password']): false; 

$result = $db->users->where('username', $username)->where('password', $password)->fetch();

if(false !== $result) {
    require('views/home.php');
} else {
    $login_errors = 'There was an error with your username or password.';
    require('views/login.php');
}
