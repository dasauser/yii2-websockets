var conn = new WebSocket('ws://localhost:21080');
conn.onopen = function(e) {
    console.log("Connection established!");
    conn.send(JSON.stringify({type: 'auth', token: 'abcdefg'}))
};

conn.onmessage = function(e) {
    console.log(e.data);
};

conn.onclose = function(e) {
    console.log('closing connection...');
}