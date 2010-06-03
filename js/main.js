YUI().use('substitute','node','event','io-base','json-parse', function(Y) { Y.on("domready", function() { 
//BEGIN Y closure
Y.one('#jqt').delegate('click', function(e) {
    e.halt();
    Y.io(this.get('href'), {
        on: {
            'success' : function(tId, response, args) {
                var rText = Y.JSON.parse(response.responseText);
            }
        }
    });

}, 'a.encodeable');

//using all due to gotcha about using #id based elementin the jqtouch cards.
Y.on('io:start', function() {
    Y.all('.spinner').removeClass('util-hide');
});
Y.on('io:complete', function() {
    Y.all('.spinner').addClass('util-hide');
});

//end closure
});});