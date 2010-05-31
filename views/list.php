<div>
<?php require 'views/toolbar.php' ?>
<ul class="edgetoedge">
    <li><a class="" href="#">Encode All</a></li>
</ul>
<?php
//Capture output, set up endoce all
extract($helper->getPaths($_GET));
if ($finalPath) { ?>    
    <ul class="videos">
    <?php
    $ite = new DirectoryIterator($finalPath);
    foreach ($ite as $file) {
        //Skip any . / .. or any file that begins with . (ie: .DS_Store) - 
        //convention is that these are hidden files anyhow.
        if($file->isDot() || (strpos($file->getFilename(), '.') === 0) ) {
            continue;
        }

        //Make the li  
        echo $helper->videoLi($file);
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