<?php
require('php/config.php');
require('php/encoder.php');

$config = new Config();
$encoder = new Encoder(array('config' => $config));

if($encoder->is_running()) {
    echo 'Encoder seems to be running already.';
    exit(0);
}

/*
while (true) {
    echo 'running
    ';
    sleep(1);
}
*/
