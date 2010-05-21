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
$row = $db->videos[13]->update(array('found'=>1));
$row = $db->videos[14]->update(array('found'=>1));
//////
foreach ($config->paths as $path) {
    echo "\n Working Path: {$path}";
    //Reset all found...
    $db->videos()->where('path LIKE ?', array("%{$path}%"))->update(array('found'=>0));

    $extensions = '-name "*.' . implode('" -o -name "*.', $config->video_extensions()) . '"';

    $command = 'find ' . escapeshellarg($path) . ' \( ' . $extensions . ' -o -name "VIDEO_TS" -o \( -name "VIDEO_TS.IFO" -a ! -wholename "*/VIDEO_TS/VIDEO_TS.IFO" \) \)';
    #echo "$command\n";
    exec($command, $videos);
    
    // var_dump($extensions);
}

//Purge from system. For now, just mark found as 2
$db->videos()->where('found = ?', array(0))->update(array('found'=>2));

echo "\nDone";
