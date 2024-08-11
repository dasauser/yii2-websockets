var conn = new WebSocket('ws://localhost:21080?token=validToken');
conn.onopen = function(e) {
    console.log("Connection established!");
};

conn.onmessage = function(e) {
    console.log(e.data);
};

conn.onclose = function(e) {
    console.log('closing connection...');
}