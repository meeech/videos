YUI().use('substitute','node','event','anim', 'io-base','json-parse', function(Y) { Y.on("domready", function() { 
//BEGIN Y closure
Y.one('#jqt').delegate('click', function(e) {
    e.halt();
    Y.io(this.get('href'), {
        on: {
            'success' : function(tId, response, args) {
                var rText = Y.JSON.parse(response.responseText);
                console.log(rText);
            }
        }
    });

}, 'a.encodeable');

// Y.on('io:start', function() {
//     console.log('start');
//     console.log(Y.one('#spinner').addClass('hide'));
// });
// Y.on('io:complete', function() {
//     //Y.one('#spinner').addClass('util-hide');
// });

//end closure
});});