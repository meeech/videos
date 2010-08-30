<?php require_once 'php/encoding_info.php'; ?>
<?php

/* AJAX check  */
if(isset($_GET['type']) && 'json' == $_GET['type']) {
    header('Content-type: text/json');
    echo json_encode($nowEncoding);
    die();
}

/* not ajax, do more.... */

?>
<div id="encode-queue">
<?php require 'views/toolbar.php' ?>
<ul class="edgetoedge">
    <?php foreach ($db->queue()->order('added ASC') as $qItem): ?>
        <?php
        $class='';
        $percentDone = false;
        if($qItem['error'] > 0) {
            $class='error error_'.$qItem['error'];
        } 
        elseif(!empty($nowEncoding) &&  $nowEncoding['filename'] == $qItem['file']) {
            $class='encoding';
            $percentDone = $nowEncoding['percent'];
        }
        ?>
        <li class="<?php echo $class?>">
            <?php if ($percentDone): ?>
                <!-- @bookmark  When cancel, set error code, so encode can move onto next one. -->
                <div class="cancel-button"><a href="encode.php?cancel" class="redButton">Cancel</a></div>
                <div class="percent-done"><?php echo  $percentDone ?>%</div>
                
            <?php endif ?>
            <span class="name"><?php echo  trim(basename($qItem['file'])); ?></span>
            <div class="small"><?php echo  dirname($qItem['file'])?></div>
        </li>
    <?php endforeach ?>
    
</ul>

</div>