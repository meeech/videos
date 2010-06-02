<?php require_once 'php/config.php'; ?>
<div id="home">
    <?php require 'views/toolbar.php' ?>
    
    <ul class="rounded">
    <?php
    //Pull paths from config - this represents the dirs we'll drill down to.
    foreach ($config->paths as $path) { ?>
        <li><a href="index.php?page=list&path=<?=$path?>"><?= $path ?></a></li>
    <?php 
    } 
    ?>
    </ul>

<!-- features to add -->

<!--
<ul>
    <li><a href="#page">Favorites</a></li>
</ul> 
-->

    <?php require 'views/footer.php' ?>    
</div>
