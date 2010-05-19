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

    

}
