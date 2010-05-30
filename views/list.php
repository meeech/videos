<?php
//Move to config
$filesToIgnore = array('.DS_Store');

//This can obviously be done more securely. 
//maybe we'll just make it id based (well, array index key for now)
$path = false;
if(isset($_GET['path']) && in_array($_GET['path'], $config->paths)) {
    $path = $_GET['path'];
}
?>
<div>
<?php require 'views/toolbar.php' ?>
<?php
if ($path) { ?>
    
    <ul class="videos">
    <?php
    $ite = new DirectoryIterator($path);
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
            $link = 'index.php?';
            $link .= 'page=list';
            //Right now, keeping path and subpath
            //path can only be one of the values in $config->paths, and sub is the rest
            $link .= "&amp;path={$path}";
            //Sounds silly, but we entitize the & otherwise we end up with 
            //&sub being 'interpreted'
            $link .= '&amp;sub=' . str_replace($path, '', $file->getPathname());
        }
        
        echo "<li class='{$class}'>";
        echo "<a href='{$link}' class='{$class}'>" . $file->getFilename() . "</a>";
        // echo $file->getType().'<br>';
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