<?php
/**
* DB Access
*/
require('php/notorm/NotORM.php');
$config = new Config();
$pdo = new PDO("{$config->dbtype}:dbname={$config->dbname}", $config->username, $config->password);
$db = new NotORM($pdo);