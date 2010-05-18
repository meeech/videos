<?php
/**
* Class to encapsulate configs. 
* At a later date, can move the configs to DB
*/
class Config {

    var $mediainfo = '/usr/local/bin/mediainfo';
    var $mplayer = '/usr/local/bin/mplayer';

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
