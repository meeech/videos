<div id="home">
    <?php require 'views/toolbar.php' ?>
    
    <ul class="rounded">    
    <?php
    //Pull paths from config - this represents the dirs we'll drill down to.
    //look to use RecursiveDirectoryIterator
    // dir(string directory, [resource context])
    $config = new Config();
    foreach ($config->paths as $path) { ?>
        <li><?= $path ?></li>
    <?php 
    } 
    ?>
    </ul>
    <ul class="rounded">
        <li class="arrow"><a href="views/login.php">Login</a></li>
    </ul>

    <ul class="rounded">
        <li class="arrow"><a href="views/login.php">Login</a></li>
    </ul>
    <ul class="individual">
        <li><a href="logout.php">Logout</a></li>
        <li><a href="http://tinyurl.com/support-jqt" target="_blank">Donate</a></li>
    </ul>
    <div class="info">
        <p>Add this page to your home screen to view the custom icon, startup screen, and full screen mode.</p>
    </div>
</div>
