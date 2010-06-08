<div id="encode-queue">
    <?php //@todo extract
    //Fetch Currently Encoding Data - not using file since it doesn't consistently recog line endings
    $logInfo = explode( "\r",file_get_contents($config->encodeLog));
    //Current encoding filename
    preg_match('/ -i \'(.*)\' -o /', current($logInfo), $regs);
    $nowEncoding = array();
    if( !empty($regs) && isset($regs[1]) ) {
        $nowEncoding['filename'] = $regs[1];
    }
    //If we can extract this, we can poll and update...
    //Maybe also pull ETA?
    $percent = end($logInfo);
    preg_match('/.*, (.*) %/', $percent, $perRegs);
    if(!empty($perRegs) && isset($perRegs[1])) {
        $nowEncoding['percent'] = $perRegs[1];
    }
    ?>

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
        <li class="<?=$class?>">
            <?php if ($percentDone): ?>
                <div class="percent-done"><?=$percentDone?>%</div>                
            <?php endif ?>
            <?= trim(basename($qItem['file'])); ?><div class="small"><?= dirname($qItem['file'])?></div>
        </li>
    <?php endforeach ?>
    
</ul>

</div>