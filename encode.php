#! /usr/bin/php 
<?php
/**
 * encode.php
 *
 * script to trigger the encoding process.
 * Can be called from CLI or Cron
 * Make executable (hence the #!)
 * @require php5.3 (though should work in 5.2)
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

//Debug
$row = $db->files[13]->update(array('encode'=>1));
//////

//Basic idea is we're doing a batch at a time so we don't overload server, 
//And so we dont have worry about how big db gets.
$file = false;
do {
    //Look for an item, mark off filesRemaining, and exit if we're done here. 
    $file = $db->files()->where('encode > 0')->limit($config->batchSize)->fetch();
    if(!$file) { 
        echo "\nNo files remaining";
        continue; 
    }
    //Begin Encoding here.
    if(!file_exists($file['path'])) {
        echo $file['path'] . ' not found.***';
        //Pipe to log?
        continue;
    }
    
    // VIDEO_TS folders
    if (is_dir($file['path'])) {
        $output_file = $file['path'] . '.' . $config->encode_extension;
    }
    else {
        $output_file = dirname($file['path']) . '/' . pathinfo($file['path'],PATHINFO_FILENAME) .'.'.$config->encode_extension;
        echo $output_file;
/*
        $output_file = substr($row->path, 0, strrpos($row->path, '.')) . '.' . $encode_extension;
        if ($output_file == $row->path) {
            // Oops!
            if ($encode_extension == 'amp4') {
                $output_file = substr($row->path, 0, strrpos($row->path, '.')) . '.m4v';
            } else {
                $output_file = substr($row->path, 0, strrpos($row->path, '.')) . '.amp4';
            }
        }
*/
    }
    
    
    
    $file->update(array('encode'=>0));
    //Debug
    echo "\nencoding ". $file['path'];

    sleep(1);
} while ($file);

echo "\nDone";
