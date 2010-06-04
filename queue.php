<?php
/**
 * Queue up selected video
 *
 * @author Mitchell Amihod
 */
require_once 'php/header.php';
$filepath = (isset($_GET['file'])) ? realpath($_GET['file']) : false;

$result = '';
switch (true) {

    case (false == $filepath):
        $result = 'no_filepath';
    break;
    
    case (!file_exists($filepath)):
        $result = 'file_no_exist';
    break;

    case ((bool)$db->queue()->where('file LIKE ?',array($filepath))->limit(1)->count()):
        $result = 'already_queued';
    break;

    default://Add to the queue
        $db->queue(array('file'=>$filepath));
        $result = 'file_queued';
    break;
}

echo json_encode(compact('result'));
