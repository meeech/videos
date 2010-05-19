#! /usr/bin/php 
<?php
/**
 * encode.php
 *
 * script to trigger the encoding process.
 * Can be called from CLI or Cron
 * Make executable (hence the #!)
 *
 * @author Mitchell Amihod
 */
require('php/config.php');
require('php/encoder.php');
require('php/db.php');

$config = new Config();
$encoder = new Encoder(array('config' => $config));

if($encoder->is_running()) {
    echo 'Encoder seems to be running already.';
    exit(0);
}

//No time limit on the encoding process
set_time_limit(0);

$pdo = new PDO("{$config->dbtype}:dbname={$config->dbname}", $config->username, $config->password);
$db = new NotORM($pdo);



//Basic idea is we're doing a batch at a time so we don't overload server, 
//And so we dont have worry about how big db gets.
$file = false;
do {
    //Look for an item, mark off filesRemaining, and exit if we're done here. 
    $file = $db->files()->where('encode > 0')->limit($config->batchSize)->fetch();

    // $filesRemaining = (bool)$item->count();
    if(!$file) { 
        echo 'No files remaining';
        continue; 
    }
    //Begin Encoding here.
    if(!file_exists($file['path'])) {
        echo $file['path'];
    }
    $file->update(array('encode'=>0));
    //Debug
    // var_dump($item->count());
    sleep(1);
} while ($file);

echo "\nDone";
