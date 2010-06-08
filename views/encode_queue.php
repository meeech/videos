<div id="encode-queue">
<?php require 'views/toolbar.php' ?>
<ul class="edgetoedge">
    <?php foreach ($db->queue()->order('added ASC') as $qItem): ?>
        <li><?= (basename($qItem['file'])); ?>
            <div class="small"><?= dirname($qItem['file'])?></div>
        </li>
    <?php endforeach ?>
    
</ul>

</div>