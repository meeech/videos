<?php //@todo extract
//Fetch Currently Encoding Data - not using file since it doesn't consistently recog line endings
$logInfo = explode( "\r",file_get_contents($config->encodeLog));
//Current encoding filename
preg_match('/ -i \'(.*)\' -o /', current($logInfo), $regs);
$nowEncoding = array();
if( !empty($regs) && isset($regs[1]) ) {
    $nowEncoding['basename'] = basename($regs[1]);
    $nowEncoding['filename'] = $regs[1];
}
//If we can extract this, we can poll and update...
//Maybe also pull ETA?
$percent = end($logInfo);
preg_match('/.*, (.*) %/', $percent, $perRegs);
if(!empty($perRegs) && isset($perRegs[1])) {
    $nowEncoding['percent'] = $perRegs[1];
}