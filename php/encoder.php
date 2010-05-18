<?php

/**
* Class to handle file encoding
*/
class Encoder{

    var $config = null;

    function __construct($settings = array()){
        $this->config = $settings['config'];
    }
    
    /**
     * Main action. Begin the encoding process
     *
     * @return void
     **/
    function go() {
        
    }
    
    /**
     * Checks if either the php encode script is running,
     * OR of HandBrakeCLI is running.
     *
     * @return bool
     **/
    function is_running() {
        $handbrake = (bool)trim(exec('ps aux | grep "HandBrakeCLI" | grep -v grep | wc -l'));
        
        //Since this one is running already, we need to make sure we not doubling up.
        $encodeScript = (2 == trim(exec('ps aux | grep "encode.php" | grep -v grep | wc -l')));
        return ($handbrake || $encodeScript);
    }
    
}
