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

$pdo = new PDO("{$config->dbtype}:dbname={$config->dbname}", $config->username,$config->password);
$db = new NotORM($pdo);

foreach( $db->files()->where('encode > 0')->limit(1) as $item ) { 
    //encode...    
    //mark as done
    //var_dump($item['encode']);
    $item['encode'] = "0";
    $item->update(array('encode'=>0));
    //var_dump($item['encode']);
    echo $item['path'];
    // sleep(1);
}

/*
while (true) {
    echo 'running
    ';
    sleep(1);
}
*/
