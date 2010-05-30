<div>
<?php require 'views/toolbar.php' ?>
<ul class="edgetoedge">
    <li><a class="" href="#">Encode All</a></li>
</ul>
<?php
//Capture output, set up endoce all

//This sets finalPath, requestPath
extract($helper->getPaths($_GET));
if ($finalPath) { ?>
    
    <ul class="videos">
    <?php
    $ite = new DirectoryIterator($finalPath);
    foreach ($ite as $file) {
        $fileName = $file->getFilename();
        //Skip any . / .. or any file that begins with . (ie: .DS_Store) - 
        //convention is that these are hidden files anyhow.
        if($file->isDot() || '.' == $fileName[0]) {
            continue;
        }
        
        
        //@extract
        //Make the li        
        $liTemp = '<li class="%1$s">%2$s</li>';
        $linkTemp = '<a href="%3$s" class="%1$s">%2$s</a>';

        $class = '' ;
        $link = '';

        //Set up link, class for directory
        if($file->isDir()) {
            $class = 'directory';
            //BUild up the link.
            $link = 'index.php?page=list';
            //Right now, keeping path and subpath
            //path can only be one of the values in $config->paths, and sub is the rest
            $link .= "&amp;path={$requestPath}";
            //we entitize the & otherwise we end up with &sub being 'interpreted'
            $link .= '&amp;sub=' . str_replace($requestPath, '', $file->getPathname());
        }
        elseif ('mp4' == pathinfo($fileName,PATHINFO_EXTENSION)) {
            //File. For now, maybe we just assume an .mp4 is playable? Do we need to check this 
            //html5 ready status?
            $class = 'movie' ;
            $link = '';
        } elseif (in_array(pathinfo($fileName,PATHINFO_EXTENSION), $config->video_extensions)) {
            $class = 'encodeable';
            
        }

        $link = sprintf($linkTemp, $class,$fileName,$link);
        echo sprintf($liTemp, $class, $link);
        
    }
    ?>
    </ul>
<?php 
} 
else { ?>
    <div class="info">Sorry. Something seems wrong with your path.</div>        
<?php } ?>

    <?php require 'views/footer.php' ?>
</div>