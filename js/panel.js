!function($, window, document) {
    var ws = null;
    var webConsoleSocket = function() {
        return new WebSocket("wss://" + location.host + "/webconsole/");
    };
    ws = webConsoleSocket();
    setInterval(function() {
        ws = open();
    }, 60000);
}
(jQuery, window, document);