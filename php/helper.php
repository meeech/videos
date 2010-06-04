<?php 
/**
* Some useful utils
*/
class Helper {

    public $config = false;
    public $db = false;
    
    public $requestPath = false;
    
    public $finalPath = false;

    function __construct($config, $db) {
        $this->config = $config;
        $this->db = $db;
    }

    /**
     * Generate the LI for a vidoe
     * @param object $file SplFileInfo
     * @return string
     **/
    function videoLi($file) {

        $liTemp = '<li class="%1$s">%2$s</li>';
        $linkTemp = '<a href="%3$s" class="%1$s">%2$s</a>';

        $class = $link = '';
        $fileName = $file->getFilename();

        if($file->isDir()) { //Set up link, class for directory
            $class = 'directory';
            //BUild up the link. Right now, keeping path and subpath
            //path can only be one of the values in $config->paths, and sub is the rest
            $link = 'index.php?page=list';
            $link .= "&amp;path={$this->requestPath}";
            //we entitize the & otherwise we end up with &sub being 'interpreted'
            $link .= '&amp;sub=' . str_replace($this->requestPath, '', $file->getPathname());
        }
        elseif (('mp4' == pathinfo($fileName,PATHINFO_EXTENSION) ) || ('m4v' == pathinfo($fileName,PATHINFO_EXTENSION))) {
            //File. For now, maybe we just assume an .mp4 is playable? Do we need to check this html5 ready status?
            $class = 'movie' ;
            $link = 'index.php?page=play';
        } 
        elseif (in_array(pathinfo($fileName,PATHINFO_EXTENSION), $this->config->video_extensions)) {
            $fullFilePath = $this->finalPath.'/'.$fileName;
            $class = ( $this->db->queue('file = ?', $fullFilePath)->count() ) ? 'queued' : 'encodeable' ;
            $link = 'queue.php?file='.$fullFilePath;
        }

        $link = sprintf($linkTemp, $class,$fileName,$link);
        echo sprintf($liTemp, $class, $link);
    }

    /**
     * Used to calculate the final and request path. 
     * sets those helper propeties at the same time.
     *
     * This whole chunk is basically to check the incoming path request. 
     * Make sure no one trying to be too clever. This can prolly be done more securely.
     * Maybe we'll just make path id based (well, array index key for now)
     * 
     * @param array $get The $_GET array
     * @return array 
     *          requestPath => path that maps to one in the settings
     *          finalPath =>  requestPath + subpath (path to the movie/directory)
     *
     **/
    function getPaths($get) {
        
        $finalPath = $requestPath = false;
        if(isset($get['path']) && in_array($get['path'], $this->config->paths)) {
            $finalPath = $requestPath = realpath($get['path']);
            //Add on subpath if it exists
            if(isset($get['sub']) && $finalPath) {
                //COnfirm they aren't trying to break out, like with ../../..
                $finalPath = realpath($requestPath . $get['sub']);
                if(false === strpos($finalPath, $requestPath)) {
                    $finalPath = false;
                }
            }
        }

        $this->requestPath = $requestPath;
        $this->finalPath = $finalPath;

        return compact('finalPath','requestPath');
    }

    function is_html5_ready($real_file, $video_codec, $audio_codec) {
        $ext = substr($real_file, strrpos($real_file, '.')+1);

        // H.264 or MPEG-4 video
        $browser = $this->to_human_codec($video_codec) == 'H.264' || $this->to_human_codec($video_codec) == 'MPEG-4';

        // MP4 or MOV container (Android doesn't support MOV; we'll handle that below...)
        $browser &= (strpos($ext, 'mp4') !== FALSE || strpos($ext, 'm4v') !== FALSE || strpos($ext, 'mov') !== FALSE);

        // AAC or MP3 audio (or no audio!)
        $browser &= empty($audio_codec) || strpos($audio_codec, 'AAC') === 0 || strpos($audio_codec, 'MPEG Audio Layer 3') === 0;

        // Mobile (iPhone/iPod Touch) only support Baseline profile, up to level 3.0, with AAC-LC audio
        $mobile = $browser 
            && (strpos($video_codec, 'Baseline@L1') !== FALSE 
                || strpos($video_codec, 'Baseline@L2') !== FALSE 
                || strpos($video_codec, 'Baseline@L3.0') !== FALSE 
                || strpos($video_codec, 'Simple@') !== FALSE
                || $video_codec == 'MPEG-4 Visual')
            && (strpos($audio_codec, 'AAC LC') !== FALSE || empty($audio_codec));

        // Android doesn't support MOV
        $android = $mobile && strpos($ext, 'mov') === FALSE;

        // iPad only support Baseline profile, up to level 3.1, with AAC-LC audio
        $ipad = $browser 
            && (strpos($video_codec, 'Baseline@L1') !== FALSE 
                || strpos($video_codec, 'Baseline@L2') !== FALSE 
                || strpos($video_codec, 'Baseline@L3.0') !== FALSE 
                || strpos($video_codec, 'Baseline@L3.1') !== FALSE 
                || strpos($video_codec, 'Main@L1') !== FALSE 
                || strpos($video_codec, 'Main@L2') !== FALSE 
                || strpos($video_codec, 'Main@L3.0') !== FALSE 
                || strpos($video_codec, 'Main@L3.1') !== FALSE 
                || strpos($video_codec, 'High@L3.0') !== FALSE 
                || strpos($video_codec, 'High@L3.1') !== FALSE 
                || strpos($video_codec, 'Simple@') !== FALSE
                || $video_codec == 'MPEG-4 Visual')
            && (strpos($audio_codec, 'AAC LC') !== FALSE || empty($audio_codec));

        $html5_ready = array();
        if ($browser) {
            $html5_ready[] = 'browser';
        }
        if ($mobile) {
            $html5_ready[] = 'mobile';
        }
        if ($ipad) {
            $html5_ready[] = 'ipad';
        }
        if ($android) {
            $html5_ready[] = 'android';
        }


        return implode(',',$html5_ready);
    }

    function video_infos(&$real_file) {
        if (strpos($real_file, 'VIDEO_TS') == strlen($real_file) - 8) {
            $command = $this->config->mplayer().' -identify -dvd-device ' . escapeshellarg($real_file) . ' dvd://1 -vo null -ao null -frames 0 2>&1 | grep -m 50 "AUDIO\|VIDEO\|mplayer\|fail\|audio stream"';
            exec($command, $infos);
        } else if (strpos($real_file, 'VIDEO_TS.IFO') == strlen($real_file) - 12) {
            $real_file = substr($real_file, 0, strlen($real_file) - 13);
            $command = $this->config->mplayer().' -identify -dvd-device ' . escapeshellarg($real_file) . ' dvd://1 -vo null -ao null -frames 0 2>&1 | grep -m 50 "AUDIO\|VIDEO\|mplayer\|fail\|audio stream"';
            exec($command, $infos);
        } else {
            $command = 'export LANG="en_US.UTF-8"; '.$this->config->mediainfo().' ' . escapeshellarg($real_file) . ' | grep "Video\|Audio\|Format\|Width\|Height\|^Text\|Language"';
            exec($command, $infos);
            if (count($infos) == 0) {
                $command = $this->config->mplayer().' ' . escapeshellarg($real_file) . ' | grep "Video\|Audio\|Format\|Width\|Height\|^Text\|Language"';
                exec($command, $infos);
                if (count($infos) == 0) {
                    $command = $this->config->mplayer().' -identify ' . escapeshellarg($real_file) . ' -vo null -ao null -frames 0 2>&1 | grep -m 50 "AUDIO\|VIDEO\|mplayer\|fail"';
                    exec($command, $infos);
                }
            }
        }
        $width = $height = 0;
        $video_codec = $audio_codec = '';
        $mode = 'container';
        foreach ($infos as $info) {
            // MPlayer
            if (strpos($info, 'ID_VIDEO_WIDTH') === 0) {
                $width = (int) substr($info, strpos($info, '=')+1);
            }
            else if (strpos($info, 'ID_VIDEO_HEIGHT') === 0) {
                $height = (int) substr($info, strpos($info, '=')+1);
            }
            else if (strpos($info, 'ID_VIDEO_CODEC') === 0) {
                $video_codec = substr($info, strpos($info, '=')+1);
            }
            else if (strpos($info, 'ID_AUDIO_CODEC') === 0) {
                $audio_codec = substr($info, strpos($info, '=')+1);
            }
            else if (strpos($info, 'audio stream') === 0) {
                $audio_codec = substr($info, strpos($info, 'format: ')+8);
                $audio_codec = substr($audio_codec, 0, strpos($audio_codec, '('));
                $audio_codec = substr($audio_codec, 0, strpos($audio_codec, ' '));
            }
            // MediaInfo
            else if (strpos($info, 'Width') === 0) {
                $width = (int) str_replace(array(' pixels', ' '), '', substr($info, strpos($info, ':')+2));
            }
            else if (strpos($info, 'Height') === 0) {
                $height = (int) str_replace(array(' pixels', ' '), '', substr($info, strpos($info, ':')+2));
            }
            else if (strpos($info, 'Video') === 0) {
                $mode = 'Video';
            }
            else if (strpos($info, 'Audio') === 0) {
                $mode = 'Audio';
            }
            else if (strpos($info, 'Text') === 0) {
                $mode = 'Text';
            }
            else if (strpos($info, 'Format  ') === 0) {
                if ($mode == 'Video') {
                    $info = substr($info, strpos($info, ':')+2);
                    if ($info == 'SRT' || $info == 'ASS') {
                        $mode = 'Text';
                        continue;
                    }
                    $video_codec = $info;
                } else if ($mode == 'Audio') {
                    if ($audio_codec != '') {
                        $audio_codec .= '+';
                    }
                    $audio_codec .= substr($info, strpos($info, ':')+2);
                }
            }
            else if (strpos($info, 'Format version') === 0) {
                if ($mode == 'Video') {
                    $video_codec .= ' v.' . str_replace('Version ', '', substr($info, strpos($info, ':')+2));
                }
            }
            else if (strpos($info, 'Format profile') === 0) {
                if ($mode == 'Video') {
                    $video_codec .= ' ' . substr($info, strpos($info, ':')+2);
                } else if ($mode == 'Audio') {
                    $audio_codec .= ' ' . substr($info, strpos($info, ':')+2);
                }
            }
            else if (strpos($info, 'Language') === 0) {
                if ($mode == 'Audio') {
                    $lang = substr($info, strpos($info, ':')+2);
                    if ($lang != 'English') {
                        $audio_codec .= ' (' . substr($info, strpos($info, ':')+2) . ')';
                    }
                }
            }
        }
        if ($video_codec == '') {
            $video_codec == 'unknown';
        }
        if ($audio_codec == '') {
            $audio_codec == 'unknown';
        }
        return compact(
            'video_codec', 
            'audio_codec', 
            'width', 
            'height', 
            'infos'
        );
    }


    function to_human_codec($codec) {
        // MPlayer
        if ($codec == 'ffdivx') return 'MPEG-4 DivX';
        if ($codec == 'ffodivx') return 'MPEG-4';
        if ($codec == 'ffdv') return 'DV';
        if ($codec == 'ffh264') return 'H.264';
        if ($codec == 'ffvc1') return 'VC-1';
        if ($codec == 'ffwmv3') return 'WMV';
        if ($codec == 'mpegpes') return 'MPEG-2';
        // Audio
        if ($codec == 'a52') return 'AC-3';
        if ($codec == 'faad') return 'AAC';
        if ($codec == 'ffadpcmimaqt') return 'ADPCM';
        if ($codec == 'ffvorbis') return 'Ogg Vorbis';
        // MediaInfo
        if ($codec == 'DX50') return 'MPEG-4 DivX';
        if ($codec == 'FMP4') return 'MPEG-4';
        if (strtoupper($codec) == 'XVID') return 'MPEG-4 XviD';
        if (strpos($codec, 'AVC') !== FALSE) return 'H.264';
        if (strpos($codec, 'MPEG Video v.1') !== FALSE) return 'MPEG-1';
        if (strpos($codec, 'MPEG Video v.2') !== FALSE) return 'MPEG-2';
        if (strpos($codec, 'MPEG-4 Visual') !== FALSE) return 'MPEG-4';
        if (strpos($codec, 'VC-1') !== FALSE) return 'VC-1';
        // Audio
        if ($codec == 'MPEG Audio') return 'MPEG';
        if ($codec == 'MPEG Audio Layer 2') return 'MP2';
        if ($codec == 'MPEG Audio Layer 3') return 'MP3';
        if ($codec == 'Vorbis') return 'Ogg Vorbis';
        return strtoupper($codec);
    }

}

//Right now, we assume config always available - thats the pattern
if(!isset($db)) {
    $db = false;
}
$helper = new Helper($config, $db);