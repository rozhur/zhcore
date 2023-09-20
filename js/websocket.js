!function($, window, document) {
    var ws = null;

    function open() {
        var ws = new WebSocket("wss://" + location.host + "/worker/?user_id=" + core.visitor.user_id + '&session_id=' + core.visitor.session_id + '&connection=' + core.uniqueId);
        for (var key in core.wsEvents) {
            for (var i = 0; i < core.wsEvents[key].length; i++) {
                ws.addEventListener(key, core.wsEvents[key][i]);
            }
        }
        return ws;
    }

    ws = open();
    setInterval(function() {
        ws = open();
    }, 60000);
}
(jQuery, window, document);