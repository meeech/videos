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
// $row = $db->videos[13]->update(array('encode'=>1, 'error'=>0));
//////

//Basic idea is we're doing a batch at a time so we don't overload server, 
//And so we dont have worry about how big db gets.
$file = false;
do {
    
    //Remember - using fetch just grabs the first record in this result. 
    //Batch size is currently being ignored. 
    $file = $db->queue()
        ->where('error = 0')
        ->order('added ASC')
        ->fetch();

    if(!$file) { 
        echo "\nNo files remaining";
        continue; 
    }

    //Sanity check, make sure the file exists
    if(!file_exists($file['file'])) {
        $file->update(array('error'=>Encoder::FILE_NOT_FOUND));
        echo '***' . $file['file'] . ' not found.***';
        continue;
    }
    
    // Check if we've queued a VIDEO_TS folders
    if (is_dir($file['file'])) {
        $output_file = $file['file'] . '.' . $config->encode_extension;
    }
    else {
        $pathInfo = pathinfo($file['file']);
        $output_file = $pathInfo['dirname'] .'/'.$pathInfo['filename'].'.'.$config->encode_extension;
    }
    
    //Trigger the encoding. Would be good if we could pipe the % done info somewhere to make it visible in browser
    //Look at how get_encoding_progress does it
    $command = sprintf($config->encode_command, escapeshellarg($file['file']), escapeshellarg($output_file));
    echo "Launching encode: $command\n";
    passthru($command);

    //Confirm the file was made
    if(!file_exists($output_file)){
        $file->update(array('error'=>Encoder::OUTPUT_FILE_NOT_FOUND));
        echo "\nError: Encoded File not created.";
    } else {
        //Remove from queue
        $file->delete();
    }

    sleep(1);
// } while ($file);
} while (false);

echo "\nDone";
