var conn = new WebSocket('ws://localhost:21080?token=invalidToken');
conn.onopen = function(e) {
    console.log("Connection established!");
};

conn.onmessage = function(e) {
    console.log(e.data);
};