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
require('php/header.php');

$config = new Config();
$encoder = new Encoder(array('config' => $config));

if($encoder->is_running()) {
    echo 'Encoder seems to be running already.';
    exit(0);
}

//No time limit on the encoding process
set_time_limit(0);

//Debug
$row = $db->files[13]->update(array('encode'=>1, 'error'=>0));
//////

//Basic idea is we're doing a batch at a time so we don't overload server, 
//And so we dont have worry about how big db gets.
$file = false;
do {
    
    //Remember - using fetch just grabs the first record in this result. 
    //Batch size is currently being ignored. 
    $file = $db->files()
        ->where('encode > 0')
        ->where('error = 0')
        ->limit($config->batchSize)
        ->fetch();

    if(!$file) { 
        echo "\nNo files remaining";
        continue; 
    }

    //Sanity check, make sure the file exists
    if(!file_exists($file['path'])) {
        $file->update(array('error'=>1));
        echo '***' . $file['path'] . ' not found.***';
        continue;
    }
    
    // Check if we've queued a VIDEO_TS folders
    if (is_dir($file['path'])) {
        $output_file = $file['path'] . '.' . $config->encode_extension;
    }
    else {
        $pathInfo = pathinfo($file['path']);
        $output_file = $pathInfo['dirname'] .'/'.$pathInfo['filename'].'.'.$config->encode_extension;
    }
    
    //Trigger the encoding. Would be good if we could pipe the % done info somewhere to make it visible in browser
    //Look at how get_encoding_progress does it
    $command = sprintf($config->encode_command, escapeshellarg($file['path']), escapeshellarg($output_file));
    echo "Launching encode: $command\n";
    passthru($command);

    //Confirm the file was made
    if(!file_exists($output_file)){
        echo "\nError: Encoded File not created.";
    }

    //Then all the Browser / Video info crazyness stuff. extract into own class

    //Remove from queue
    $file->update(array('encode'=>0));

    //Insert video
    
    sleep(1);
} while ($file);

echo "\nDone";
