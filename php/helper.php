<?php 
/**
* Some useful utils
*/
class Helper {

    public $config = false;

    function __construct($config) {
        $this->config = $config;
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
        return array($video_codec, $audio_codec, $width, $height, $infos);
    }


}
