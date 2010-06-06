<?php
/**
* Class to encapsulate configs. 
* At a later date, can move the configs to DB
*/
class Config {

    //DB
    var $dbtype   = 'mysql';
    var $dbname   = 'videos5';
    var $username = 'videos5';
    var $password = 'videos5';
    
    //Path to folders we want to look for videos
    //Can be anywhere accessible to the script
    //
    //@todo look into allow http/ftp/ access?
    var $paths = array(
        '/Users/mitch/Sites/videos5/dropbox',
        '/Users/mitch/Sites/videos',
        '/Users/mitch/Desktop/tmp/Glee'
    );

    var $symlinks = 'videos';

    //Binaries
    var $mediainfo = '/usr/local/bin/mediainfo';
    var $mplayer = '/usr/local/bin/mplayer';

    /**
     * How many videos to pull from db at a time.
     *
     * @var int
     **/
    var $batchSize = 1;

    /**
     * The encode command. A sprintf ready.
     * %1$s input
     * %2$s output
     *
     * @var string
     **/
    var $encode_command = '/usr/bin/nice /usr/local/bin/HandBrakeCLI -L -i %1$s -o %2$s -e x264 -q 20.0 -a 1 -E faac -B 160 -6 dpl2 -R 48 -D 0.0 -f mp4 -X 720 -Y 480 --loose-anamorphic -m -x cabac=0:ref=2:me=umh:bframes=0:8x8dct=0:trellis=0:subme=6';


    /**
     * List of file types to offer to encode.
     *
     * @var string
     **/
    var $video_extensions = array('m4v', 'mp4', 'ts', 'mov', 'divx', 'xvid', 'vob', 'm2v', 'avi', 'mpg', 'mpeg', 'mkv', 'm2t', 'm2ts');

    /**
     * undocumented class variable
     *
     * @var string
     **/
    var $encode_extension = 'm4v';

    var $ratings_definitions = array(
        'Unrated' => '',
        'G' => 'General audience',
        'PG' => 'Parental guidance suggested',
        'PG-13' => 'Parents strongly cautioned',
        'R' => 'Restricted',
        'NC-17' => 'No one 17 and under admitted',
        'TV-Y' => 'All children',
        'TV-Y7' => 'Children 7 and older',
        'TV-G' => 'General audience',
        'TV-PG' => 'Parental guidance suggested',
        'TV-14' => 'May be unsuitable for children under 14',
        'TV-MA' => 'Mature audience'
    );

    public function __call($name, $args) {
        if(property_exists($this,$name)) {
            return $this->$name;
        }
    }

}

$config = new Config();