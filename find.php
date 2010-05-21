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
$helper = new Helper($config);

// if($encoder->is_running()) {
//     echo 'Encoder seems to be running already.';
//     exit(0);
// }

//No time limit on the finding process
set_time_limit(0);

//Debug
// $row = $db->videos[13]->update(array('found'=>1));
// $row = $db->videos[14]->update(array('found'=>1));
//////

foreach ($config->paths as $path) {
    echo "Working Path: {$path}\n";

    //Reset all found...
    $db->videos()->where('path LIKE ?', array("%{$path}%"))->update(array('found'=>0));

    //Build the find command
    $extensions = '-name "*.' . implode('" -o -name "*.', $config->video_extensions()) . '"';
    $command = 'find ' . escapeshellarg($path) . ' \( ' . $extensions . ' -o -name "VIDEO_TS" -o \( -name "VIDEO_TS.IFO" -a ! -wholename "*/VIDEO_TS/VIDEO_TS.IFO" \) \)';

    exec($command, $videos);
    foreach ($videos as $videopath) {
        echo "Scanning $videopath \n";
        $vidInfo = $helper->video_infos($path);
        $vidInfo['path'] = $videopath;
        $vidInfo['resolution'] = $vidInfo['width'].'x'.$vidInfo['height'];
        $vidInfo['found'] = 1;
        $vidInfo['html5_ready'] = $helper->is_html5_ready($videopath, $vidInfo['video_codec'], $vidInfo['audio_codec']);
        unset($vidInfo['infos'], $vidInfo['height'], $vidInfo['width'] );
                
        $video = $db->videos()
            ->where('path LIKE ?', array($videopath))
            ->fetch();

        if(!$video) {
            echo "Creating new video.\n";
            $video = $db->videos($vidInfo);//create
        } else {
            echo "Updating.\n";
            $video->update($vidInfo);
        }
    }
}

//Purge from system. 
//Don't worry about queue table. foreign key constraint setup
$db->videos()->where('found = ?', array(0))->delete();

echo "Done";
