<?php
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
if ($path) {
//list out all the files & folders
} 
else { ?>
        <div class="info">Sorry. Something seems wrong with your path.</div>        
<?php
}
?>

</div>