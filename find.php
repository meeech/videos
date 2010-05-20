#! /usr/bin/php 
<?php
/**
 * find.php
 *
 * cron rebuild index every X
 * Allow for manual trigger in front end
 * 
 * find unregistered videos, add to register
 *
 * Make executable (hence the #!)
 * @require php5.3 (though should work in 5.2)
 * (thats part of the fun of hobby projects!)
 * @author Mitchell Amihod
 */
require('php/header.php');

$config = new Config();
// $encoder = new Encoder(array('config' => $config));

// if($encoder->is_running()) {
//     echo 'Encoder seems to be running already.';
//     exit(0);
// }

//No time limit on the finding process
set_time_limit(0);

//Debug
$row = $db->files[13]->update(array('found'=>'yes'));
//////
foreach ($config->encodePaths as $path) {
    echo "\n Working Path: {$path}";
    $db->files()->where('path LIKE ?', array("%{$path}%"))->update(array('found'=>'no'));

    

}
echo "\nDone";
