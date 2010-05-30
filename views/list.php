<?php
//This whole chunk is basically to check the incoming path request. 
//Make sure no one trying to be too clever. 
//This can obviously be done more securely. 
//maybe we'll just make it id based (well, array index key for now)
$finalPath = $requestPath = false;
if(isset($_GET['path']) && in_array($_GET['path'], $config->paths)) {
    $finalPath = $requestPath = realpath($_GET['path']);
    //Add on subpath if it exists
    if(isset($_GET['sub']) && $finalPath) {
        //COnfirm they aren't trying to break out, like with ../../..
        $finalPath = realpath($requestPath . $_GET['sub']);
        if(false === strpos($finalPath, $requestPath)) {
            $finalPath = false;
        }
    }
}
/////
?>
<div>
<?php require 'views/toolbar.php' ?>
<?php
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

        $class = 'movie' ;
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
        else {
            //File. For now, maybe we just assume an .mp4 is playable? Do we need to check this 
            //html5 ready status?
        }
        
        echo "<li class='{$class}'>";
        echo "<a href='{$link}' class='{$class}'>" . $file->getFilename() . "</a>";
        //echo $link.'<br>';
        // echo $file->getPathname().'<br>';
        echo '</li>';
        
    }
    ?>
    </ul>
    <?php
    
} 
else { ?>
        <div class="info">Sorry. Something seems wrong with your path.</div>        
<?php
}
?>

</div>