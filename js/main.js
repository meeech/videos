
YUI().use('substitute','node','event','io-base','json-parse','datasource-function', 'datasource-polling', 'dump', function(Y) { Y.on("domready", function() { 
//BEGIN Y closure

//Queues up an item to encode.
Y.one('#jqt').delegate('click', function(e) {
    e.halt();
    if(this.hasClass('encodeable')) {
        Y.io(this.get('href'), {
            'on': {
                'success' : function(tId, response, args) {
                    var rText = Y.JSON.parse(response.responseText);
                    this.replaceClass('encodeable', 'queued');
                }
            },
            'context': this //THe clicked element
        });        
    }

}, 'ul.videos li a.encodeable,ul.videos li a.queued');

//Global IO Event listeners - show/hide spinner
Y.on('io:start', function() { Y.one('#spinner').removeClass('util-hide'); });
Y.on('io:complete', function() { Y.one('#spinner').addClass('util-hide'); });

// One trick is to use live instead of bind for panels you loading via ajax
$('#encode-queue').live('pageAnimationEnd', function(e,data) {
    if('in' == data.direction) {
        // console.log(this);
        Y.io('index.php?page=encode_queue&type=json',{
            'on': {
                'success' : function(tId, response, args) {
                    var rText = Y.JSON.parse(response.responseText);
                    Y.one('li.encoding .percent-done').setContent(rText.percent);
                }
            }
        });
        
    } else {
        //Hmm, maybe we dont want to bother turning it off till done. why shouldnt we update the info the bg.
//            console.log('eq page out - stop updating');
    }

});



//end closure
});});

$(document).ready(function(e){        
    // Code to deal with the video playing to stop it.
    // @bug / gotcha
    // Spent about an hour in chrome trying to figure out why this wasn't working.
    // Turns out chrome hasn't implemented  video.pause(), so thats why it was failing.
    // So, on chrome, the video will keep playing in the background unless you explicitly
    // stop it. As this is mainly aimed at mobile, thats a limitation we can live with for now.
    $('#jqt').bind('pageAnimationStart', function(e, data){
        var currentpage = $($('.current').get(0));
        if(!currentpage.hasClass('player')) {
            return true;
        }

        if('out' == data.direction) {
            //Hmm, thought since currentpage is a $, then i could currentpage(selector)
            $('video',currentpage).get(0).pause();
        } 
        return true;
    });
    
    //IF we need to, this would be the place to cache the video.
    // $('#jqt').bind('pageAnimationEnd', function(e, data){
    //     var currentpage = $($('.current').get(0));
    //     if(!currentpage.hasClass('player')) {
    //         return true;
    //     }
    // 
    //     if('in' == data.direction) {
    //         console.log('cache currently playng video');            
    //     }
    // });
 });