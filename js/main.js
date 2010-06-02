$(document).ready(function(){
///////
$('#jqt').delegate('a.encodeable', 'click', function(e) {
    //Stop it from bubbling up - which is why we don't use preventDefault 
    //THat allows the jqtouch code to try to handle it, which then complains about 404
    // console.log(this);
    return false;    
});
///////
});