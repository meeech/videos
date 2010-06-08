<div id="encode-queue">
<?php require 'views/toolbar.php' ?>
<ul class="edgetoedge">
    <?php foreach ($db->queue()->order('added ASC') as $qItem): ?>
        <?php
        $class="";
        if($qItem['error'] > 0) {
            $class="error error_".$qItem['error'];
        }
        ?>
        <li class="<?=$class?>">
            <?= trim(basename($qItem['file'])); ?><div class="small"><?= dirname($qItem['file'])?></div>
        </li>
    <?php endforeach ?>
    
</ul>

</div>